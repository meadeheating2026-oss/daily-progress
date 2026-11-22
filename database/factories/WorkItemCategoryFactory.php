<?php

namespace Database\Factories;

use App\Models\WorkItemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkItemCategory>
 */
class WorkItemCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WorkItemCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'أعمال خرسانية',
            'أعمال حديد التسليح',
            'أعمال النجارة',
            'أعمال البلاط والسيراميك',
            'أعمال الكهرباء',
            'أعمال السباكة',
            'أعمال الدهانات',
            'أعمال العزل',
            'أعمال التشطيبات',
            'أعمال الحفر والردم',
            'أعمال الألمنيوم',
            'أعمال الزجاج',
            'أعمال الأسقف المعلقة',
            'أعمال الأرضيات',
            'أعمال التكييف',
        ];

        return [
            'name' => fake()->unique()->randomElement($categories),
        ];
    }
}
