<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\SpaceImageController;
use App\Http\Controllers\StorageRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SpaceController as AdminSpaceController;
use App\Http\Controllers\Admin\QuoteController as AdminQuoteController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use Illuminate\Support\Facades\Route;

// --- ÁREA PÚBLICA ---
Route::post('/companies', [CompanyController::class, 'store']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Rota de listagem global (apenas espaços ativos/aprovados aparecem aqui devido ao filtro no Controller)
Route::get('/spaces', [SpaceController::class, 'index']);
Route::get('/spaces/{id}', [SpaceController::class, 'show']);

Route::get('/product-types', function () {
    return response()->json(App\Enums\ProductType::getOptions());
});

// --- ÁREA SEGURA (Precisa de Token) ---
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // CRUD de Empresas (Perfil)
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::put('/companies/{id}', [CompanyController::class, 'update']);
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);

    // ROTAS DE ESPAÇOS (Câmaras Frias)
    Route::get('/my-spaces', [SpaceController::class, 'mySpaces']); // Ver apenas os meus
    Route::post('/spaces', [SpaceController::class, 'store']);
    Route::put('/spaces/{id}', [SpaceController::class, 'update']);
    Route::delete('/spaces/{id}', [SpaceController::class, 'destroy']);

    // Rotas de Solicitação de Armazenagem
    Route::get('/storage-requests', [StorageRequestController::class, 'index']);
    Route::post('/storage-requests', [StorageRequestController::class, 'store']);
    Route::put('/storage-requests/{id}', [StorageRequestController::class, 'update']);
    Route::delete('/storage-requests/{id}', [StorageRequestController::class, 'destroy']);
    Route::get('/storage-requests/{id}/matches', [MatchController::class, 'index']);

    // Negociação / Cotações
    Route::get('/quotes', [QuoteController::class, 'index']);
    Route::post('/quotes', [QuoteController::class, 'store']);
    Route::put('/quotes/{id}', [QuoteController::class, 'update']);
    Route::post('/quotes/offer', [QuoteController::class, 'offer']);
    Route::delete('/quotes/{id}', [QuoteController::class, 'destroy']);

    // Identidade
    Route::get('/me', [AuthController::class, 'me']);

    // Fotos do Espaço
    Route::post('/spaces/{id}/photos', [SpaceImageController::class, 'store']);
    Route::delete('/photos/{id}', [SpaceImageController::class, 'destroy']);

    // Dashboard Geral (Stats)
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // Notificações (Leitura e Gerenciamento)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    Route::get('/quotes/{id}/contract', [QuoteController::class, 'downloadContract']);

    // Pagamentos (com rate limiting)
    Route::get('/payments', [PaymentController::class, 'index'])->middleware('throttle:30,1');
    Route::get('/payments/{id}', [PaymentController::class, 'show'])->middleware('throttle:30,1');
    Route::post('/quotes/{quoteId}/payment', [PaymentController::class, 'create'])->middleware('throttle:10,1');
    Route::post('/payments/{id}/process', [PaymentController::class, 'process'])->middleware('throttle:10,1');
    Route::get('/payments/{id}/status', [PaymentController::class, 'status'])->middleware('throttle:60,1');

// --- ROTAS DE ADMIN ---
Route::middleware('admin')->prefix('admin')->group(function () {
    // Listagem geral
    Route::get('/spaces', [AdminSpaceController::class, 'index']); // Gera: /api/admin/spaces

    // CORREÇÃO AQUI: Mude de '/admin/stats' para '/stats'
    Route::get('/stats', [AdminDashboardController::class, 'stats']); // Gera: /api/admin/stats
    
    // Ação de Análise (Aprovar/Rejeitar)
    Route::post('/spaces/{id}/analyze', [AdminSpaceController::class, 'analyze']);

    // Empresas - listagem para admin
    Route::get('/companies', [AdminCompanyController::class, 'index']);
    Route::get('/companies/{id}/quotes', [AdminCompanyController::class, 'companyQuotes']);

    // Cotações - aprovação/rejeição
    Route::get('/quotes', [AdminQuoteController::class, 'index']);
    Route::post('/quotes/{id}/approve', [AdminQuoteController::class, 'approve']);
    Route::post('/quotes/{id}/reject', [AdminQuoteController::class, 'reject']);
    
    // Exportação CSV
    Route::get('/export-users', [CompanyController::class, 'exportUsersCsv']);
    
    // Pagamentos (Admin) - com rate limiting mais restritivo
    // IMPORTANTE: Rotas específicas (stats) devem vir ANTES de rotas com parâmetros ({id})
    Route::get('/payments/stats', [AdminPaymentController::class, 'stats'])->middleware('throttle:30,1');
    Route::get('/payments', [AdminPaymentController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/payments/{id}', [AdminPaymentController::class, 'show'])->middleware('throttle:60,1');
    Route::post('/payments/{id}/transfer', [AdminPaymentController::class, 'transfer'])->middleware('throttle:20,1');
    Route::post('/payments/{id}/refund', [AdminPaymentController::class, 'refund'])->middleware('throttle:20,1');
});
});

// Webhook de pagamento (público, com validação de assinatura e rate limiting)
// Rate limiting mais permissivo para webhooks (gateways podem enviar múltiplos)
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->middleware('throttle:100,1');