@extends('admin.layouts.auth')

@section('title', 'Iniciar Sesión')
@section('heading', 'Iniciar Sesión')
@section('subheading', 'Acceso al Panel Administrativo')

@section('content')
<form x-data="{ loading: false }" 
      @submit="loading = true" 
      method="POST" 
      action="{{ route('admin.login.post') }}" 
      class="space-y-6">
    @csrf

    <!-- Campo Email -->
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">
            Correo Electrónico
        </label>
        <div class="mt-1">
            <input id="email" 
                   name="email" 
                   type="email" 
                   autocomplete="email" 
                   required 
                   value="{{ old('email') }}"
                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-300 @enderror"
                   placeholder="admin@adoc.com">
        </div>
        @error('email')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Campo Contraseña -->
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">
            Contraseña
        </label>
        <div class="mt-1" x-data="{ showPassword: false }">
            <div class="relative">
                <input :type="showPassword ? 'text' : 'password'" 
                       id="password" 
                       name="password" 
                       autocomplete="current-password" 
                       required 
                       class="appearance-none block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-300 @enderror"
                       placeholder="••••••••">
                
                <button type="button" 
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <svg x-show="!showPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L12 12m-2.122-2.122L9.878 9.878m4.242 4.242L12 12m2.121-2.121L14.12 9.88" />
                    </svg>
                </button>
            </div>
        </div>
        @error('password')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Recordarme (opcional) -->
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <input id="remember-me" 
                   name="remember" 
                   type="checkbox" 
                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                Recordarme
            </label>
        </div>
    </div>

    <!-- Botón de envío -->
    <div>
        <button type="submit" 
                :disabled="loading"
                :class="loading ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'"
                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors">
            
            <!-- Spinner de carga -->
            <svg x-show="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            
            <span x-text="loading ? 'Iniciando sesión...' : 'Iniciar Sesión'">Iniciar Sesión</span>
        </button>
    </div>

    <!-- Información adicional -->
    <div class="mt-6 text-center">
        <p class="text-xs text-gray-500">
            ¿Problemas para acceder? Contacte al administrador del sistema.
        </p>
    </div>
</form>

<!-- Credenciales de prueba (solo en desarrollo) -->
@if(config('app.debug'))
<div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
    <h4 class="text-sm font-medium text-yellow-800 mb-2">Credenciales de prueba:</h4>
    <p class="text-xs text-yellow-700">
        <strong>Email:</strong> admin@adoc.com<br>
        <strong>Contraseña:</strong> password
    </p>
</div>
@endif
@endsection