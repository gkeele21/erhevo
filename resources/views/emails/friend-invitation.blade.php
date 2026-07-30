@component('mail::message')
{{ __(':inviter has invited you to join :app!', ['inviter' => $inviterName, 'app' => config('app.name')]) }}

{{ __(':app is a place where words lift you. As a member you can:', ['app' => config('app.name')]) }}

- {{ __('Capture and share stories, thoughts, and quotes that matter to you') }}
- {{ __('Build lessons or write your own talks from scriptures, quotes, and your own ideas — then teach or speak right from the app') }}
- {{ __('Create study plans for scriptures or conference talks, with a schedule that fits your pace and tracks your progress') }}

{{ __('Create your account by clicking the button below, and you and :inviter will automatically be connected as friends:', ['inviter' => $inviterName]) }}

@component('mail::button', ['url' => $registerUrl])
{{ __('Join :app', ['app' => config('app.name')]) }}
@endcomponent

{{ __('If you did not expect to receive this invitation, you may discard this email.') }}
@endcomponent
