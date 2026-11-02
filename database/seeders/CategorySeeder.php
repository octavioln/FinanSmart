<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Es idempotente: usa el nombre como clave natural.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Coche', 'description' => 'Gasolina, reparaciones, mantenimientos.'],
            ['name' => 'Entretenimiento', 'description' => 'Streaming, cine, teatro, conciertos, videojuegos'],
            ['name' => 'Comida', 'description' => 'Frutas, verduras, carnes, agua, comida rápida'],
            ['name' => 'Artículos personales', 'description' => 'Papel higiénico, cepillos,'],
            ['name' => 'Casa', 'description' => 'Limpiadores, detergentes, reparaciones'],
            ['name' => 'Salud', 'description' => 'Medicinas, consultas, radiografías, estudios'],
            ['name' => 'Ropa', 'description' => 'Camisas, pantalones, zapatos, sombreros'],
            ['name' => 'Ahorro', 'description' => 'Dinero destinado a cuentas de ahorro e inversión', 'system_key' => Category::SYSTEM_SAVINGS],
            ['name' => 'Suministros', 'description' => 'Agua, luz, gas'],
            ['name' => 'Saldo', 'description' => 'Saldo para los teléfonos'],
            ['name' => 'Mascotas', 'description' => 'Croquetas, arena, veterinaria, medicinas'],
            ['name' => 'Cigarros', 'description' => 'Cigarros, habanos, tabaco'],
            ['name' => 'Alcohol', 'description' => 'Cerveza, tequila, vino, licor'],
            ['name' => 'Créditos', 'description' => 'Tarjetas de crédito, tiendas departamentales, automotriz, hipotecario'],
            ['name' => 'Colegiatura', 'description' => 'Pago a escuelas'],
            ['name' => 'Gimnasio', 'description' => 'Mensualidades del gimnasio'],
            ['name' => 'Otro', 'description' => 'Otro tipo de gasto que normalmente no está contemplado'],
            ['name' => 'Traspaso entre cuentas', 'description' => 'De débito a ahorro, de ahorro a débito, de débito a efectivo', 'system_key' => Category::SYSTEM_TRANSFER],
            ['name' => 'Depósito', 'description' => 'De débito a ahorro, de ahorro a débito, de débito a efectivo', 'system_key' => Category::SYSTEM_DEPOSIT],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
