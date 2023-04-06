<x-app-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
        </x-slot>

        <x-jet-validation-errors class="mb-4"/>

        <form method="POST" action="{{ route('registerUser') }}">
            @csrf
            <input name="id" type="hidden" value="{{$user->id ?? ''}}">
            <div>
                <x-jet-label for="name" value="{{ __('Name') }}"/>
                <x-jet-input id="name" class="block mt-1 w-full"
                             type="text"
                             name="name"
                             value="{{$user->name ?? ''}}" required
                             autofocus autocomplete="name"/>
            </div>

            <div class="mt-4">
                <x-jet-label for="email" value="{{ __('Email') }}"/>
                <x-jet-input id="email" class="block mt-1 w-full" type="email" name="email" value="{{$user->email ?? ''}}"
                             required/>
            </div>

            <div class="mt-4">
                <x-jet-label for="project" value="{{ __('Проект') }}"/>
                <x-jet-input id="project" class="block mt-1 w-full" type="text" name="project" value="{{$user->project ?? ''}}"
                             required/>
            </div>

            <div class="mt-4">
                <x-jet-label for="password" value="{{ __('Password') }}" />
                <x-jet-input id="password" class="block mt-1 w-full" type="password" name="password" autocomplete="new-password" />
            </div>

            <div class="mt-4">
                <x-jet-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-jet-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" autocomplete="new-password" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-jet-button class="ml-4">
                    @if (!isset($user->id))
                        {{ __('Создать') }}
                    @else
                        {{ __('Сохранить') }}
                    @endif
                </x-jet-button>
            </div>
        </form>
    </x-jet-authentication-card>
</x-app-layout>
