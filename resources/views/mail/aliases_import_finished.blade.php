@component('mail::message')

# Aliases Import Finished

Your import CSV file had **{{ $totalRows }}** {{ $totalRows == 1 ? 'alias' : 'aliases' }} in total. **{{ $totalImported }}** {{ $totalImported == 1 ? 'alias has' : 'aliases have' }} been successfully imported.

@if($totalNotImported)
**{{ $totalNotImported }}** {{ $totalNotImported == 1 ? 'alias was' : 'aliases were' }} not imported. Details on why {{ $totalNotImported == 1 ? 'this alias was' : 'these aliases were' }} not imported can be seen below:

@if($totalFailures)
- **{{ $totalFailures }}** {{ $totalFailures == 1 ? 'alias' : 'aliases' }} had validation failures
@endif
@if($totalErrors)
- **{{ $totalErrors }}** {{ $totalErrors == 1 ? 'alias was a duplicate (already exists in the database)' : 'aliases were duplicates (already exist in the database)' }}
@endif
@endif

You can view your newly imported aliases by visiting your account below.

@component('mail::button', ['url' => config('app.url')])
View Aliases
@endcomponent
@endcomponent
