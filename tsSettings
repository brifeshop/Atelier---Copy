[1mdiff --git a/app/Models/User.php b/app/Models/User.php[m
[1mindex 7066b94..ffddc11 100644[m
[1m--- a/app/Models/User.php[m
[1m+++ b/app/Models/User.php[m
[36m@@ -5,12 +5,13 @@[m
 use Database\Factories\UserFactory;[m
 use Illuminate\Database\Eloquent\Attributes\Fillable;[m
 use Illuminate\Database\Eloquent\Attributes\Hidden;[m
[32m+[m[32muse Illuminate\Database\Eloquent\Builder;[m
 use Illuminate\Database\Eloquent\Factories\HasFactory;[m
 use Illuminate\Foundation\Auth\User as Authenticatable;[m
 use Illuminate\Notifications\Notifiable;[m
 use Spatie\Permission\Traits\HasRoles;[m
 [m
[31m-#[Fillable(['name', 'email', 'password'])][m
[32m+[m[32m#[Fillable(['name', 'email', 'password', 'is_active'])][m
 #[Hidden(['password', 'remember_token'])][m
 class User extends Authenticatable[m
 {[m
[36m@@ -27,6 +28,15 @@[m [mprotected function casts(): array[m
         return [[m
             'email_verified_at' => 'datetime',[m
             'password' => 'hashed',[m
[32m+[m[32m            'is_active' => 'boolean',[m
         ];[m
     }[m
[32m+[m
[32m+[m[32m    /**[m
[32m+[m[32m     * Scope query untuk user yang aktif.[m
[32m+[m[32m     */[m
[32m+[m[32m    public function scopeActive(Builder $query): Builder[m
[32m+[m[32m    {[m
[32m+[m[32m        return $query->where('is_active', true);[m
[32m+[m[32m    }[m
 }[m
\ No newline at end of file[m
[1mdiff --git a/resources/views/layouts/app.blade.php b/resources/views/layouts/app.blade.php[m
[1mindex 70b640f..ac3f4a6 100644[m
[1m--- a/resources/views/layouts/app.blade.php[m
[1m+++ b/resources/views/layouts/app.blade.php[m
[36m@@ -137,7 +137,15 @@[m [mclass="mx-auto mt-4 w-10 h-10 rounded-lg text-navy-500 hover:text-gold-500 hover[m
 [m
                             // Lainnya[m
                             ['route' => null, 'label' => 'Laporan', 'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'group' => 'Lainnya'],[m
[31m-                            ['route' => null, 'label' => 'Pengaturan', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'group' => 'Lainnya'],[m
[32m+[m[32m                            [[m
[32m+[m[32m                                'label' => 'Pengaturan',[m
[32m+[m[32m                                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',[m
[32m+[m[32m                                'group' => 'Lainnya',[m
[32m+[m[32m                                'submenu' => [[m
[32m+[m[32m                                    ['route' => 'settings.users.index', 'label' => 'Users'],[m
[32m+[m[32m                                    ['route' => null, 'label' => 'Roles'],[m
[32m+[m[32m                                ],[m
[32m+[m[32m                            ],[m
                         ];[m
                     @endphp[m
 [m
[36m@@ -150,62 +158,144 @@[m [mclass="mx-auto mt-4 w-10 h-10 rounded-lg text-navy-500 hover:text-gold-500 hover[m
                                  x-transition:enter-start="opacity-0"[m
                                  x-transition:enter-end="opacity-100"[m
                                  class="pt-4 pb-2 px-3">[m
[31m-                                <p class="text-[10px] font-semibold text-navy-500 uppercase tracking-widest">{{ $item['group'] }}</p>[m
[32m+[m[32m                                 <p class="text-[10px] font-semibold text-navy-500 uppercase tracking-widest">{{ $item['group'] }}</p>[m
                             </div>[m
                             <div x-show="!sidebarOpen" class="pt-3"></div>[m
                         @endif[m
 [m
[31m-                        @php[m
[31m-                            $isActive = $item['route'] && request()->routeIs($item['route'] . '*');[m
[31m-                        @endphp[m
[32m+[m[32m                        @if(isset($item['submenu']))[m
[32m+[m[32m                            {{-- Dropdown Menu --}}[m
[32m+[m[32m                            @php[m
[32m+[m[32m                                $hasSubActive = false;[m
[32m+[m[32m                                foreach ($item['submenu'] as $sub) {[m
[32m+[m[32m                                    if ($sub['route'] && request()->routeIs($sub['route'] . '*')) {[m
[32m+[m[32m                                        $hasSubActive = true;[m
[32m+[m[32m                                        break;[m
[32m+[m[32m                                    }[m
[32m+[m[32m                                }[m
[32m+[m[32m                            @endphp[m
 [m
[31m-                        <a href="{{ $item['route'] ? route($item['route']) : '#' }}"[m
[31m-                            class="group relative flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200[m
[31m-                                    {{ $isActive [m
[31m-                                        ? 'bg-gold-500 text-navy-900 shadow-lg shadow-gold-500/20' [m
[31m-                                        : 'text-navy-300 hover:text-white hover:bg-navy-800' }}"[m
[31m-                            :class="!sidebarOpen && 'justify-center'">[m
[31m-                                [m
[31m-                                {{-- Active Indicator Bar --}}[m
[31m-                                @if($isActive)[m
[31m-                                    <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-navy-900 rounded-r-full -ml-3"></div>[m
[31m-                                @endif[m
[32m+[m[32m                            <div x-data="{ open: {{ $hasSubActive ? 'true' : 'false' }} }" class="relative">[m
[32m+[m[32m                                <button @click="open = !open"[m[41m [m
[32m+[m[32m                                    type="button"[m
[32m+[m[32m                                    class="w-full group relative flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200[m
[32m+[m[32m                                            {{ $hasSubActive[m[41m [m
[32m+[m[32m                                                ? 'bg-navy-800 text-gold-400 font-semibold'[m[41m [m
[32m+[m[32m                                                : 'text-navy-300 hover:text-white hover:bg-navy-800' }}"[m
[32m+[m[32m                                    :class="!sidebarOpen && 'justify-center'">[m
[32m+[m[41m                                    [m
[32m+[m[32m                                    @if($hasSubActive)[m
[32m+[m[32m                                        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-gold-500 rounded-r-full -ml-3"></div>[m
[32m+[m[32m                                    @endif[m
 [m
[31m-                                <!-- Icon -->[m
[31m-                                <svg class="w-5 h-5 flex-shrink-0 {{ $isActive ? 'stroke-[2.5]' : '' }}" [m
[31m-                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">[m
[31m-                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>[m
[31m-                                </svg>[m
[32m+[m[32m                                    <!-- Icon -->[m
[32m+[m[32m                                    <svg class="w-5 h-5 flex-shrink-0 {{ $hasSubActive ? 'text-gold-500' : '' }}"[m[41m [m
[32m+[m[32m                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">[m
[32m+[m[32m                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>[m
[32m+[m[32m                                    </svg>[m
 [m
[31m-                                <!-- Label -->[m
[31m-                                <span x-show="sidebarOpen"[m
[31m-                                    x-transition:enter="transition ease-out duration-200"[m
[31m-                                    x-transition:enter-start="opacity-0 -translate-x-2"[m
[31m-                                    x-transition:enter-end="opacity-100 translate-x-0"[m
[31m-                                    class="text-sm font-medium whitespace-nowrap overflow-hidden">[m
[31m-                                    {{ $item['label'] }}[m
[31m-                                </span>[m
[31m-[m
[31m-                                {{-- Badge Low Stock --}}[m
[31m-                                @if($item['route'] === 'warehouse.low-stocks.index' && $lowStockCount > 0)[m
[32m+[m[32m                                    <!-- Label -->[m
                                     <span x-show="sidebarOpen"[m
[31m-                                        class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold rounded-full bg-red-500 text-white">[m
[31m-                                        {{ $lowStockCount }}[m
[32m+[m[32m                                        x-transition:enter="transition ease-out duration-200"[m
[32m+[m[32m                                        x-transition:enter-start="opacity-0 -translate-x-2"[m
[32m+[m[32m                                        x-transition:enter-end="opacity-100 translate-x-0"[m
[32m+[m[32m                                        class="text-sm font-medium whitespace-nowrap overflow-hidden flex-1 text-left">[m
[32m+[m[32m                                        {{ $item['label'] }}[m
                                     </span>[m
[31m-                                @endif[m
 [m
[31m-                                <!-- Tooltip (saat collapsed) -->[m
[31m-                                <div x-show="!sidebarOpen"[m
[31m-                                    class="absolute left-full ml-3 px-3 py-1.5 bg-navy-800 text-white text-xs font-medium rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap transition-opacity z-50 shadow-xl border border-navy-700">[m
[31m-                                    {{ $item['label'] }}[m
[32m+[m[32m                                    <!-- Chevron -->[m
[32m+[m[32m                                    <svg x-show="sidebarOpen"[m[41m [m
[32m+[m[32m                                        class="w-4 h-4 text-navy-400 transition-transform duration-200 flex-shrink-0"[m
[32m+[m[32m                                        :class="open ? 'rotate-180 text-gold-500' : ''"[m
[32m+[m[32m                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">[m
[32m+[m[32m                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>[m
[32m+[m[32m                                    </svg>[m
[32m+[m
[32m+[m[32m                                    <!-- Tooltip (saat collapsed) -->[m
[32m+[m[32m                                    <div x-show="!sidebarOpen"[m
[32m+[m[32m                                        class="absolute left-full ml-3 px-3 py-1.5 bg-navy-800 text-white text-xs font-medium rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap transition-opacity z-50 shadow-xl border border-navy-700">[m
[32m+[m[32m                                        {{ $item['label'] }}[m
[32m+[m[32m                                        <div class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1 w-2 h-2 bg-navy-800 rotate-45 border-l border-b border-navy-700"></div>[m
[32m+[m[32m                                    </div>[m
[32m+[m[32m                                </button>[m
[32m+[m
[32m+[m[32m                                <!-- Submenu Links -->[m
[32m+[m[32m                                <div x-show="open && sidebarOpen"[m[41m [m
[32m+[m[32m                                     x-transition:enter="transition ease-out duration-150"[m
[32m+[m[32m                                     x-transition:enter-start="opacity-0 -translate-y-1"[m
[32m+[m[32m                                     x-transition:enter-end="opacity-100 translate-y-0"[m
[32m+[m[32m                                     class="pl-8 pr-2 py-1 space-y-1">[m
[32m+[m[32m                                    @foreach($item['submenu'] as $subItem)[m
[32m+[m[32m                                        @php[m
[32m+[m[32m                                            $isSubActive = $subItem['route'] && request()->routeIs($subItem['route'] . '*');[m
[32m+[m[32m                                        @endphp[m
[32m+[m[32m                                        <a href="{{ $subItem['route'] ? route($subItem['route']) : '#' }}"[m
[32m+[m[32m                                           class="flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition[m
[32m+[m[32m                                                  {{ $isSubActive[m[41m [m
[32m+[m[32m                                                      ? 'bg-gold-500 text-navy-900 font-bold shadow-sm'[m[41m [m
[32m+[m[32m                                                      : ($subItem['route'] ? 'text-navy-300 hover:text-white hover:bg-navy-800/60' : 'text-navy-500 cursor-not-allowed') }}">[m
[32m+[m[32m                                            <span>{{ $subItem['label'] }}</span>[m
[32m+[m[32m                                            @if(!$subItem['route'])[m
[32m+[m[32m                                                <span class="text-[9px] uppercase tracking-wider px-1 py-0.5 rounded bg-navy-800 text-navy-500 border border-navy-700">Nanti</span>[m
[32m+[m[32m                                            @endif[m
[32m+[m[32m                                        </a>[m
[32m+[m[32m                                    @endforeach[m
[32m+[m[32m                                </div>[m
[32m+[m[32m                            </div>[m
[32m+[m[32m                        @else[m
[32m+[m[32m                            {{-- Standard Menu Item --}}[m
[32m+[m[32m                            @php[m
[32m+[m[32m                                $isActive = $item['route'] && request()->routeIs($item['route'] . '*');[m
[32m+[m[32m                            @endphp[m
[32m+[m
[32m+[m[32m                            <a href="{{ $item['route'] ? route($item['route']) : '#' }}"[m
[32m+[m[32m                                class="group relative flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200[m
[32m+[m[32m                                        {{ $isActive[m[41m [m
[32m+[m[32m                                            ? 'bg-gold-500 text-navy-900 shadow-lg shadow-gold-500/20'[m[41m [m
[32m+[m[32m                                            : 'text-navy-300 hover:text-white hover:bg-navy-800' }}"[m
[32m+[m[32m                                :class="!sidebarOpen && 'justify-center'">[m
[32m+[m[41m                                    [m
[32m+[m[32m                                    {{-- Active Indicator Bar --}}[m
[32m+[m[32m                                    @if($isActive)[m
[32m+[m[32m                                        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-navy-900 rounded-r-full -ml-3"></div>[m
[32m+[m[32m                                    @endif[m
[32m+[m
[32m+[m[32m                                    <!-- Icon -->[m
[32m+[m[32m                                    <svg class="w-5 h-5 flex-shrink-0 {{ $isActive ? 'stroke-[2.5]' : '' }}"[m[41m [m
[32m+[m[32m                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">[m
[32m+[m[32m                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>[m
[32m+[m[32m                                    </svg>[m
[32m+[m
[32m+[m[32m                                    <!-- Label -->[m
[32m+[m[32m                                    <span x-show="sidebarOpen"[m
[32m+[m[32m                                        x-transition:enter="transition ease-out duration-200"[m
[32m+[m[32m                                        x-transition:enter-start="opacity-0 -translate-x-2"[m
[32m+[m[32m                                        x-transition:enter-end="opacity-100 translate-x-0"[m
[32m+[m[32m                                        class="text-sm font-medium whitespace-nowrap overflow-hidden">[m
[32m+[m[32m                                        {{ $item['label'] }}[m
[32m+[m[32m                                    </span>[m
[32m+[m
[32m+[m[32m                                    {{-- Badge Low Stock --}}[m
                                     @if($item['route'] === 'warehouse.low-stocks.index' && $lowStockCount > 0)[m
[31m-                                        <span class="ml-2 inline-flex items-center justify-center min-w-[18px] h-4 px-1 text-[10px] font-bold rounded-full bg-red-500 text-white">[m
[32m+[m[32m                                        <span x-show="sidebarOpen"[m
[32m+[m[32m                                            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold rounded-full bg-red-500 text-white">[m
                                             {{ $lowStockCount }}[m
                                         </span>[m
                                     @endif[m
[31m-                                    <div class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1 w-2 h-2 bg-navy-800 rotate-45 border-l border-b border-navy-700"></div>[m
[31m-                                </div>[m
[31m-                            </a>[m
[32m+[m
[32m+[m[32m                                    <!-- Tooltip (saat collapsed) -->[m
[32m+[m[32m                                    <div x-show="!sidebarOpen"[m
[32m+[m[32m                                        class="absolute left-full ml-3 px-3 py-1.5 bg-navy-800 text-white text-xs font-medium rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap transition-opacity z-50 shadow-xl border border-navy-700">[m
[32m+[m[32m                                        {{ $item['label'] }}[m
[32m+[m[32m                                        @if($item['route'] === 'warehouse.low-stocks.index' && $lowStockCount > 0)[m
[32m+[m[32m                                            <span class="ml-2 inline-flex items-center justify-center min-w-[18px] h-4 px-1 text-[10px] font-bold rounded-full bg-red-500 text-white">[m
[32m+[m[32m                                                {{ $lowStockCount }}[m
[32m+[m[32m                                            </span>[m
[32m+[m[32m                                        @endif[m
[32m+[m[32m                                        <div class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1 w-2 h-2 bg-navy-800 rotate-45 border-l border-b border-navy-700"></div>[m
[32m+[m[32m                                    </div>[m
[32m+[m[32m                                </a>[m
[32m+[m[32m                        @endif[m
                     @endforeach[m
                 </nav>[m
 [m
[1mdiff --git a/routes/web.php b/routes/web.php[m
[1mindex 43febba..57055a2 100644[m
[1m--- a/routes/web.php[m
[1m+++ b/routes/web.php[m
[36m@@ -38,6 +38,7 @@[m
 use App\Http\Controllers\Production\MaterialIssueController;[m
 use App\Http\Controllers\Production\WorkOrderProgressController;[m
 use App\Http\Controllers\Production\FinishedGoodsReceiptController;[m
[32m+[m[32muse App\Http\Controllers\Settings\UserController;[m
 [m
 use Illuminate\Support\Facades\Route;[m
 [m
[36m@@ -284,4 +285,14 @@[m
     Route::get('costing-export', [CostingController::class, 'export'])->name('costing.export');[m
 });[m
 [m
[32m+[m[32m// ==================== SETTINGS ====================[m
[32m+[m[32mRoute::middleware(['auth', 'can:manage users'])[m
[32m+[m[32m    ->prefix('settings')[m
[32m+[m[32m    ->name('settings.')[m
[32m+[m[32m    ->group(function () {[m
[32m+[m[32m        Route::resource('users', UserController::class);[m
[32m+[m[32m        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])[m
[32m+[m[32m            ->name('users.toggle-status');[m
[32m+[m[32m    });[m
[32m+[m
 require __DIR__.'/auth.php';[m
\ No newline at end of file[m
