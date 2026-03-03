<?php

namespace App\Enums;

enum UnitType: string
{
    case Pallets = 'pallets';
    case Kg = 'kg';
    case Toneladas = 'toneladas';
    case MetrosCubicos = 'm3';
    case Caixas = 'caixas';
}
