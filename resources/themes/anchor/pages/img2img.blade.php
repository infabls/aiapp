<?php

use function Livewire\Volt\{state};
use Illuminate\Support\Facades\Http as HttpFacade;

// Определяем состояние компонента
state(['prompt' => '', 'imageUrl' => '', 'loading' => false]);

// Функция генерации изображения
$generateImage = function () {
    $this->loading = true; // Устанавливаем состояние загрузки
    $this->imageUrl = '';

    // Отправляем запрос к Replicate API
    $response = HttpFacade::withHeaders([
        'Authorization' => 'Bearer ' . env('REPLICATE_API_TOKEN'),
        'Content-Type' => 'application/json',
        'Prefer' => 'wait',
    ])->post('https://api.replicate.com/v1/models/black-forest-labs/flux-redux-dev/predictions', [
        'input' => [
            'guidance' => 3,
            'megapixels' => '1',
            'num_outputs' => 1,
            'redux_image' => $this->prompt,
            'aspect_ratio' => '4:3',
            'output_format' => 'webp',
            'output_quality' => 80,
            'num_inference_steps' => 28
        ],
    ]);

    if ($response->successful() && isset($response->json()['output'])) {
        $result = $response->json();
        $this->imageUrl = $result['output'][0] ?? 'Ошибка: Изображение не сгенерировано.';
    } else {
        $this->imageUrl = 'Ошибка: ' . $response->body();
    }

    $this->loading = false; // Завершаем состояние загрузки
};

?>

@volt
<!-- Интерфейс страницы -->
<div>
    <x-layouts.app>
        <x-app.container x-data class="lg:space-y-6" x-cloak>
            <x-app.alert id="dashboard_alert" class="hidden lg:flex">This is the user dashboard where users can manage settings and access features. <a href="https://devdojo.com/wave/docs" target="_blank" class="mx-1 underline">View the docs</a> to learn more.</x-app.alert>

            <x-app.heading
                title="Dashboard"
                description="Приветствуем. Вот кнопка генерации"
                :border="false"
            />

            <div class="mt-5 space-y-5">
                @subscriber
                    <p>You are a subscribed user with the <strong>{{ auth()->user()->roles()->first()->name }}</strong> role. Learn <a href="https://devdojo.com/wave/docs/features/roles-permissions" target="_blank" class="underline">more about roles</a> here.</p>
                    <x-app.message-for-subscriber />
                @else
                    <p>This current logged in user has a <strong>{{ auth()->user()->roles()->first()->name }}</strong> role. To upgrade, <a href="{{ route('settings.subscription') }}" class="underline">subscribe to a plan</a>. Learn <a href="https://devdojo.com/wave/docs/features/roles-permissions" target="_blank" class="underline">more about roles</a> here.</p>
                @endsubscriber
            </div>

            <!-- Форма для ввода запроса и отображение результата -->
            <div class="w-full mt-5">
                <h1 class="text-2xl font-bold mb-5">Генерация изображения по фото</h1>

                <form wire:submit.prevent="generateImage" class="mb-5">
                    <label for="prompt" class="block text-lg font-medium mb-2">Введите ссылку на фото:</label>
                    <input type="text" id="prompt" wire:model="prompt" 
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2" 
                        placeholder="Например, sunset over mountains" required>
                    <button type="submit" 
                            class="mt-3 px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50" 
                            {{ $loading ? 'disabled' : '' }}>
                        {{ $loading ? 'Генерация...' : 'Создать изображение' }}
                    </button>
                </form>
                a:

                @if ($imageUrl)
                    <div class="mt-5">
                        @if (str_contains($imageUrl, 'http'))
                            <img src="{{ $imageUrl }}" alt="Сгенерированное изображение" class="rounded-lg shadow-lg">
                        @else
                            <p class="text-red-500">{{ $imageUrl }}</p>
                        @endif
                    </div>

                    <!-- Кнопка для сохранения изображения -->
                            <a href="{{ $imageUrl }}" download="generated_image.jpg" 
                               class="mt-3 px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 inline-block">
                                Сохранить картинку
                            </a>
                @endif
            </div>
        </x-app.container>
    </x-layouts.app>
</div>
@endvolt
