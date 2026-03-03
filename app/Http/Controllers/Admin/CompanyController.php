<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\AdminQuoteResource;
use App\Models\Company;
use App\Models\Quote;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function companyQuotes(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $query = Quote::query()
            ->with(['space.company', 'storageRequest.company'])
            ->where(function ($q) use ($id) {
                $q->whereHas('storageRequest', fn ($sq) => $sq->where('company_id', $id))
                    ->orWhereHas('space', fn ($sq) => $sq->where('company_id', $id));
            })
            ->orderBy('created_at', 'desc');

        $statuses = $request->get('status');
        if ($statuses) {
            $statusList = array_map('trim', explode(',', $statuses));
            if (in_array(QuoteStatus::Rejeitado->value, $statusList)) {
                $query->withTrashed();
            }
            $query->whereIn('status', $statusList);
        } else {
            $query->whereIn('status', [
                QuoteStatus::Respondido->value,
                QuoteStatus::Aceito->value,
                QuoteStatus::Rejeitado->value,
            ])->withTrashed();
        }

        $quotes = $query->paginate(20);

        return AdminQuoteResource::collection($quotes);
    }

    public function index(Request $request)
    {
        $query = Company::query()
            ->withCount(['spaces', 'storageRequests'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('trade_name', 'like', "%{$term}%")
                    ->orWhere('legal_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('cnpj', 'like', "%{$term}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $companies = $query->paginate(20);

        return AdminCompanyResource::collection($companies);
    }
}
