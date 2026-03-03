<?php

namespace App\Enums;

enum ProductType: string
{
    case CarnesProteinas = 'carnes_proteinas';
    case Laticinios = 'laticinios_derivados';
    case FrutasVegetais = 'frutas_vegetais';
    case Congelados = 'congelados_industrializados';
    case Farmacos = 'farmacos_vacinas';
    case Bebidas = 'bebidas';
    case FloresPlantas = 'flores_plantas';
    case Quimicos = 'quimicos_materias_primas';
    case Outros = 'outros';

    public function label(): string
    {
        return match($this) {
            self::CarnesProteinas => 'Carnes e Proteínas',
            self::Laticinios => 'Laticínios e Derivados',
            self::FrutasVegetais => 'Frutas e Vegetais',
            self::Congelados => 'Congelados Industrializados',
            self::Farmacos => 'Fármacos e Vacinas',
            self::Bebidas => 'Bebidas',
            self::FloresPlantas => 'Flores e Plantas',
            self::Quimicos => 'Químicos e Matérias-primas',
            self::Outros => 'Outros',
        };
    }

    public static function getOptions(): array
{
    return array_map(fn($case) => [
        'value' => $case->value,
        'label' => $case->label(),
    ], self::cases());
}
}