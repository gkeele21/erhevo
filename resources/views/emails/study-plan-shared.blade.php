@component('mail::message')
{{ __(':owner shared a study plan with you on :app!', ['owner' => $ownerName, 'app' => config('app.name')]) }}

**{{ $planName }}**
{{ $planSummary }}

{{ __("You're studying together: you both see the same schedule, and either of you can check a reading off — one check counts for the group.") }}

@component('mail::button', ['url' => $planUrl])
{{ __('Open the study plan') }}
@endcomponent

{{ __('Happy studying!') }}
@endcomponent
