<?php

namespace App\Imports;

use App\Models\Alias;
use App\Models\User;
use App\Notifications\AliasesImportedNotification;
use App\Rules\ValidAliasLocalPart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\ImportFailed;
use Ramsey\Uuid\Uuid;

class AliasesImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, ShouldQueue, SkipsOnFailure, SkipsEmptyRows, SkipsOnError, WithLimit, WithColumnLimit, WithEvents
{
    use Queueable, Importable, SkipsFailures, SkipsErrors, RemembersRowNumber;

    protected $user;

    protected $domains;

    protected $verfiedRecipientEmailAndIds;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->domains = $user->domains()->select(['id', 'domain'])->get();

        $this->verfiedRecipientEmailAndIds = $user
            ->verifiedRecipients()
            ->select(['email', 'id'])
            ->get()
            ->mapWithKeys(function ($recipient) {
                return [$recipient->email => $recipient->id];
            });
    }

    /**
     * @return Alias|null
     */
    public function model(array $row)
    {
        $aliasable = $this->domains->firstWhere('domain', $row['domain']);

        if (! $aliasable) {
            return null;
        }

        // Split the alias into parts
        if (Str::contains($row['local_part'], '+')) {
            $row['extension'] = Str::after($row['local_part'], '+');
            $row['local_part'] = Str::before($row['local_part'], '+');
        }

        $alias = new Alias([
            'id' => Uuid::uuid4(),
            'user_id' => $this->user->id,
            'email' => $row['local_part'].'@'.$row['domain'],
            'local_part' => $row['local_part'],
            'extension' => $row['extension'],
            'domain' => $row['domain'],
            'description' => $row['description'],
            'aliasable_id' => $aliasable->id,
            'aliasable_type' => 'App\\Models\\Domain',
        ]);

        if ($row['recipient_ids']) {
            $alias->recipients()->sync($row['recipient_ids']);
        }

        return $alias;
    }

    public function prepareForValidation($data)
    {
        // Ensure the alias is all lowercase and whitespace has been trimmed
        $data['alias'] = trim(strtolower($data['alias']));
        // Add for validation
        $data['domain'] = Str::afterLast($data['alias'], '@');
        $data['local_part'] = Str::beforeLast($data['alias'], '@');
        $data['extension'] = null;
        $data['recipient_ids'] = null;

        if (! is_null($data['description'])) {
            // Make sure it is a string
            $data['description'] = (string) $data['description'];
        }

        // Set the actual email without the extension
        if (Str::contains($data['local_part'], '+')) {
            $data['email'] = Str::before($data['local_part'], '+').'@'.$data['domain'];
        } else {
            $data['email'] = $data['local_part'].'@'.$data['domain'];
        }

        // Map emails to an array of corresponding recipient IDs
        if ($data['recipients']) {
            $recipients = explode(',', $data['recipients']);

            foreach ($recipients as $recipient) {
                if (isset($this->verfiedRecipientEmailAndIds[$recipient])) {
                    $data['recipient_ids'][] = $this->verfiedRecipientEmailAndIds[$recipient];
                }
            }
        }

        // Return only required array keys
        return collect($data)
            ->only([
                'alias',
                'email',
                'local_part',
                'extension',
                'domain',
                'description',
                'recipient_ids',
            ])
            ->all();
    }

    public function rules(): array
    {
        return [
            'alias' => [
                'bail',
                'required',
                'email',
                'max:254',
                'string',
            ],
            'email' => [
                'bail',
                'required',
                'max:254',
                'string',
            ],
            'local_part' => [
                'bail',
                'required',
                'max:64',
                'string',
                new ValidAliasLocalPart(),
            ],
            'domain' => [
                'bail',
                'required',
                'string',
                Rule::in($this->domains->pluck('domain') ?? []),
            ],
            'description' => [
                'bail',
                'nullable',
                'string',
                'max:200',
            ],
            'recipient_ids' => [
                'bail',
                'nullable',
                'array',
                'max:10',
            ],
            'recipient_ids.*' => [
                'uuid',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => [self::class, 'afterSheet'],
            ImportFailed::class => [self::class, 'importFailed'],
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        $import = $event->getConcernable();

        // Check for failures and errors
        $totalFailures = $import->failures()->groupBy(fn ($failure) => $failure->row())?->count() ?? 0;
        $totalErrors = $import->errors()->count();

        // If the import has any failures then get the row number for the last failure
        $lastFailureRowNumber = $totalFailures ? $import->failures()->sortByDesc(fn ($failure) => $failure->row())->first()?->row() : 0;

        $totalRows = $import->getRowNumber();

        // If all errors (duplicate entries) then it can get the row number
        // If every row is a validation failure getRowNumber is null
        if (is_null($totalRows) || $lastFailureRowNumber > $totalRows) {
            $totalRows = $lastFailureRowNumber;
        }

        // Minus the header row
        $totalRows = $totalRows > 0 ? $totalRows - 1 : 0;

        $totalNotImported = $totalFailures + $totalErrors;
        $totalImported = $totalRows - $totalNotImported;

        // Notify user with email.
        $import->getUser()->notify(new AliasesImportedNotification($totalRows, $totalImported, $totalNotImported, $totalFailures, $totalErrors));
    }

    public static function importFailed(ImportFailed $event)
    {
        // Log details of failure
        Log::info('AliasesImport Failure:', ['event' => $event, 'exception' => $event->getException()]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function limit(): int
    {
        return 1000;
    }

    public function endColumn(): string
    {
        return 'C';
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->user->id))->releaseAfter(180)->expireAfter(600)]; // release after 3 minutes and expire after 10
    }

    public function getUser()
    {
        return $this->user;
    }

    // For testing
    public function getDomains()
    {
        return $this->domains;
    }

    // For testing
    public function getRecipientIds()
    {
        return $this->verfiedRecipientEmailAndIds
            ->values()
            ->all();
    }
}
