@component('mail::message')
{{ __(':inviter has invited you to join :app!', ['inviter' => $inviterName, 'app' => config('app.name')]) }}

{{ __(':app is a place to capture and share stories, thoughts, and quotes that matter to you. Create your account by clicking the button below, and you and :inviter will automatically be connected as friends:', ['app' => config('app.name'), 'inviter' => $inviterName]) }}

@component('mail::button', ['url' => $registerUrl])
{{ __('Join :app', ['app' => config('app.name')]) }}
@endcomponent

{{ __('If you did not expect to receive this invitation, you may discard this email.') }}
@endcomponent
