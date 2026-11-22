<?php

namespace Database\Factories;

use App\Models\WorkItem;
use App\Models\WorkItemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkItem>
 */
class WorkItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WorkItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $itemTypes = [
            'صب خرسانة',
            'توريد وتركيب حديد',
            'أعمال نجارة',
            'تركيب بلاط',
            'تمديد كهرباء',
            'تمديد سباكة',
            'دهان جدران',
            'عزل مائي',
            'تشطيب داخلي',
            'حفر وردم',
            'نقل مخلفات',
            'توريد مواد',
            'تركيب أبواب',
            'تركيب نوافذ',
            'صيانة دورية',
            'اختبار جودة',
            'فحص سلامة',
            'معايرة أجهزة',
        ];

        $units = [
            'متر مكعب',
            'متر مربع',
            'متر طولي',
            'طن',
            'كجم',
            'قطعة',
            'لتر',
            'عدد',
            'كيس',
            'شيكارة',
        ];

        $descriptions = [
            'بند أساسي للمشروع',
            'عمل ضروري للمنشأة',
            'بند تشطيب نهائي',
            'أعمال تحضيرية',
            'أعمال إنشائية',
            'أعمال تكميلية',
            'بند اختياري',
            'أعمال صيانة',
        ];

        // Get random category or create if none exists
        $categoryId = WorkItemCategory::inRandomOrder()->first()?->id 
            ?? WorkItemCategory::factory()->create()->id;

        return [
            'name' => fake()->randomElement($itemTypes) . ' - ' . fake()->unique()->numberBetween(1, 10000),
            'unit' => fake()->randomElement($units),
            'description' => fake()->randomElement($descriptions) . ' ' . fake()->sentence(),
            'category_id' => $categoryId,
            'order' => fake()->numberBetween(1, 10000),
        ];
    }
}
