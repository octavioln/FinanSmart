<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            "name" => 'Coche',
            "description" => "Gasolina, reparaciones, mantenimientos.",
        ]);

        Category::create([
            "name" => 'Entretenimiento',
            "description" => "Streaming, cine, teatro, conciertos, videojuegos",
        ]);

        Category::create([
            "name" => 'Comida',
            "description" => "Frutas, verduras, carnes, agua, comida ráida",
        ]);

        Category::create([
            "name" => 'Artículos personales',
            "description" => "Papel higiénico, cepillos, ",
        ]);

        Category::create([
            "name" => 'Casa',
            "description" => "Limpiadores, detergentes, reparaciones",
        ]);

        Category::create([
            "name" => 'Salud',
            "description" => "Medicionas, consultas, radiografías, estudios",
        ]);

        Category::create([
            "name" => 'Ropa',
            "description" => "Camisas, pantalones, zapatos, sombreros",
        ]);

        Category::create([
            "name" => 'Ahorro',
            "description" => "Dinero destinado a cuentas de ahorro e inversión",
        ]);

        Category::create([
            "name" => 'Suministros',
            "description" => "Agua, luz, gas",
        ]);

        Category::create([
            "name" => 'Saldo',
            "description" => "Saldo para los teléfonos",
        ]);

        Category::create([
            "name" => 'Mascotas',
            "description" => "Croquetas, arena, veterinaria, medicinas",
        ]);

        Category::create([
            "name" => 'Cigarros',
            "description" => "Cigarros, habanos, tabaco",
        ]);

        Category::create([
            "name" => 'Alcohol',
            "description" => "Cerveza, tequila, vino, licor",
        ]);

        Category::create([
            "name" => 'Créditos',
            "description" => "Tarjetas de crédito, tiendas departamentales, automotriz, hipotecario",
        ]);

        Category::create([
            "name" => 'Colegiatura',
            "description" => "Pago a escuelas",
        ]);

        Category::create([
            "name" => 'Gimnasio',
            "description" => "Mensualidades del gimnasio",
        ]);

        Category::create([
            "name" => 'Otro',
            "description" => "Otro tipo de gasto que normalmente no está contemplado",
        ]);

        Category::create([
            "name" => 'Traspaso entre cuentas',
            "description" => "De débito a ahorro, de ahorro a débito, de débito a efectivo",
        ]);

        Category::create([
            "name" => 'Depósito',
            "description" => "De débito a ahorro, de ahorro a débito, de débito a efectivo",
        ]);
    }
}
