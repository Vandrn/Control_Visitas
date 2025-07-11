@if(session('admin_user.rol') === 'evaluador_pais' && session('admin_user.pais_acceso') !== 'ALL')
<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">
                Acceso Restringido por País
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
                <p>
                    Su cuenta tiene acceso únicamente a datos del país: 
                    <span class="font-semibold">{{ session('admin_user.pais_acceso') }}</span>
                </p>
                <p class="mt-1">
                    Los filtros y resultados mostrados están limitados a este país automáticamente.
                </p>
            </div>
        </div>
    </div>
</div>
@endif