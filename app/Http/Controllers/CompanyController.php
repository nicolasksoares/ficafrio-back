<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function store(StoreCompanyRequest $request)
    {
        $data = $request->validated();
        
        $data['type'] = UserType::Cliente;
        $data['active'] = true;

        $company = Company::create($data);

        return response()->json([
            'message' => 'Empresa criada com sucesso',
            'data' => $company,
        ], 201);
    }

    public function index()
    {
        return response()->json(Company::paginate(15), 200);
    }

    public function update(UpdateCompanyRequest $request, $id)
    {
        $company = Company::findOrFail($id);

        if ($request->user()->id !== $company->id) {
            return response()->json(['message' => 'Acesso proibido'], 403);
        }

        $company->update($request->validated());

        return response()->json([
            'message' => 'Empresa atualizada!',
            'data' => new CompanyResource($company),
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        if ($request->user()->id !== $company->id) {
            return response()->json(['message' => 'Acesso proibido'], 403);
        }

        $company->delete();

        return response()->json(null, 204);
    }

    public function exportUsersCsv(Request $request)
    {
        // 1. Segurança: Apenas admin
        if ($request->user()->type !== UserType::Admin) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
    
        $users = Company::all();
        $fileName = 'relatorio_completo_usuarios_' . date('d-m-Y_H-i') . '.csv';
    
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
    
        $columns = [
            'ID', 
            'Nome Fantasia', 
            'Razao Social', 
            'CNPJ', 
            'E-mail', 
            'Telefone', 
            'Rua', 
            'Numero', 
            'Bairro', 
            'Cidade', 
            'UF', 
            'CEP', 
            'Tipo de Usuario', 
            'Status', 
            'Data de Cadastro'
        ];
    
        $callback = function() use($users, $columns) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, $columns, ';');
    
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->trade_name,
                    $user->legal_name,
                    "\t" . $user->cnpj,
                    $user->email,
                    '="' . $user->phone . '"',                    $user->address_street,
                    $user->address_number,
                    $user->district,
                    $user->city,
                    $user->state,
                    $user->zip_code,
                    $user->type->value ?? $user->type, 
                    $user->active ? 'Ativo' : 'Inativo',
                    $user->created_at->format('d/m/Y H:i')
                ], ';');
            }
    
            fclose($file);
        };
    
        return response()->stream($callback, 200, $headers);
    }
}