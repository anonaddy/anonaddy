<?php

namespace App\Traits;

trait ApplyUserRules
{
    protected $emailType;

    public function applyRulesByIds(array $ruleIds)
    {
        if (empty($ruleIds)) {
            return;
        }

        $this->user->rules()->whereIn('id', $ruleIds)->each(function ($rule) {
            // Apply actions for that rule
            collect($rule->actions)->each(function ($action) {
                $this->applyAction($action);
            });
        });
    }

    protected function applyAction($action)
    {
        switch ($action['type']) {
            case 'subject':
                $this->replacedSubject = ' with subject "'.base64_decode($this->emailSubject).'"';
                $this->email->subject = $action['value'];
                break;
            case 'displayFrom':
                $this->email->from = [];
                $this->displayFrom = $action['value'];
                $this->email->from($this->fromEmail, $action['value']);
                break;
            case 'encryption':
                if ($action['value'] == false) {
                    // detach the openpgpsigner from the email...
                    if (isset($this->fingerprint)) {
                        $this->fingerprint = null;
                    }
                }
                break;
            case 'banner':
                if (in_array($action['value'], ['top', 'bottom', 'off'])) {

                    if ($this->emailHtml) {
                        // Turn off the banner for the plain text version
                        $this->bannerLocationText = 'off';
                        $this->bannerLocationHtml = $action['value'];
                    } else {
                        $this->bannerLocationText = $action['value'];
                    }
                }
                break;
            case 'block':
                // Do nothing, already checked.
                break;
            case 'removeAttachments':
                $this->emailAttachments = [];
                break;
            case 'forwardTo':
                // Do nothing, already checked.
                break;
            case 'webhook':
                // http payload to url
                break;
        }
    }
}
