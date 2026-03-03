<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 100px 50px; }
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.6; }
        .header { position: fixed; top: -70px; left: 0; right: 0; height: 50px; border-bottom: 2px solid #0ea5e9; }
        .footer { position: fixed; bottom: -60px; left: 0; right: 0; height: 30px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .title { text-align: center; color: #0f172a; text-transform: uppercase; margin-bottom: 30px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table th { background-color: #f8fafc; text-align: left; padding: 8px; border: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
        .info-table td { padding: 8px; border: 1px solid #e2e8f0; font-size: 13px; }
        .section-title { font-size: 14px; font-weight: bold; color: #0ea5e9; margin-top: 20px; margin-bottom: 10px; text-transform: uppercase; border-left: 4px solid #0ea5e9; padding-left: 10px; }
        .terms { font-size: 11px; color: #64748b; text-align: justify; }
        .signatures { margin-top: 50px; width: 100%; }
        .sig-box { width: 45%; border-top: 1px solid #333; text-align: center; padding-top: 5px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <span style="font-weight: bold; color: #0ea5e9; font-size: 20px;">❄ FicaFrio</span>
        <span style="float: right; color: #64748b; font-size: 11px;">ID da Alocação: #{{ $quote->id }}</span>
    </div>

    <div class="footer">
        Este documento é um comprovante digital de reserva de espaço gerado em {{ date('d/m/Y H:i') }}.
    </div>

    <h2 class="title">Contrato de Alocação de Espaço</h2>

    <div class="section-title">Dados das Partes</div>
    <table class="info-table">
        <tr>
            <th width="50%">Locador (Dono do Espaço)</th>
            <th width="50%">Locatário (Contratante)</th>
        </tr>
        <tr>
            <td><strong>{{ $quote->space->company->trade_name }}</strong></td>
            <td><strong>{{ $quote->storageRequest->company->trade_name }}</strong></td>
        </tr>
    </table>

    <div class="section-title">Detalhes da Operação e Carga</div>
    <table class="info-table">
        <tr>
            <th>Câmara Fria / Espaço</th>
            <td>{{ $quote->space->name }} ({{ $quote->space->city }}/{{ $quote->space->state }})</td>
        </tr>
        <tr>
            <th>Tipo de Produto</th>
            <td>{{ str_replace('_', ' ', ucfirst($quote->storageRequest->product_type->value ?? $quote->storageRequest->product_type)) }}</td>        </tr>
        <tr>
            <th>Quantidade Reservada</th>
            <td>{{ $quote->storageRequest->quantity }} Paletes</td>
        </tr>
        <tr>
            <th>Valor Total Fechado</th>
            <td style="font-size: 16px; font-weight: bold; color: #0ea5e9;">R$ {{ number_format($quote->price, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="section-title">Termos e Condições</div>
    <div class="terms">
        <p>1. A reserva confirmada através deste documento garante a disponibilidade das posições de paletes mencionadas para o período acordado via plataforma.</p>
        <p>2. O Locador declara que o espaço atende às normas sanitárias e de temperatura exigidas para o tipo de carga informado.</p>
        <p>3. <strong>Isenção de Responsabilidade:</strong> A Plataforma FicaFrio atua exclusivamente como intermediadora da negociação, não se responsabilizando pelo transporte físico, integridade da carga ou inadimplência entre as partes.</p>
        <p>4. Eventuais cancelamentos após o aceite digital estão sujeitos às multas acordadas previamente no chat de negociação.</p>
    </div>

    <table class="signatures" style="margin-top: 80px;">
        <tr>
            <td class="sig-box">{{ $quote->space->company->trade_name }}</td>
            <td width="10%"></td>
            <td class="sig-box">{{ $quote->storageRequest->company->trade_name }}</td>
        </tr>
    </table>
</body>
</html>