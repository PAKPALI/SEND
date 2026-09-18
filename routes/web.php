<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\AdminQuotaController;
use App\Http\Controllers\QuotaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->name('login.store');
    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');

    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::post('/contacts/import', [ContactController::class, 'import'])->name('contacts.import');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

    Route::get('/groupes', [GroupController::class, 'index'])->name('groups.index');
    Route::post('/groupes', [GroupController::class, 'store'])->name('groups.store');
    Route::put('/groupes/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groupes/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

    Route::get('/campagnes', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campagnes/nouvelle', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campagnes', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campagnes/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::post('/campagnes/{campaign}/renvoyer', [CampaignController::class, 'retry'])->name('campaigns.retry');

    Route::get('/historique', [HistoryController::class, 'index'])->name('history.index');
    Route::post('/historique/{recipient}/renvoyer', [HistoryController::class, 'retry'])->name('history.retry');

    Route::get('/credits', [QuotaController::class, 'index'])->name('quota.index');
    Route::post('/credits/checkout', [QuotaController::class, 'checkout'])->name('quota.checkout');
    Route::get('/credits/retour', [QuotaController::class, 'returned'])->name('quota.return');

    Route::middleware('admin')->prefix('administration')->name('admin.')->group(function (): void {
        Route::get('/quotas', [AdminQuotaController::class, 'index'])->name('quota.index');
        Route::post('/quotas/{payment}/valider', [AdminQuotaController::class, 'approve'])->name('quota.approve');
        Route::post('/quotas/{payment}/refuser', [AdminQuotaController::class, 'reject'])->name('quota.reject');
    });
});
