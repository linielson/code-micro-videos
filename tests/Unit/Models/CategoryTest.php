<?php

namespace Tests\Unit;

use App\Models\Category;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\Uuid;

class CategoryTest extends TestCase
{
    public function testIfUseTraits()
    {
        $traits = [SoftDeletes::class, Uuid::class];
        $categoryTraits = array_keys(class_uses(Category::class));
        $this->assertEquals($traits, $categoryTraits);
    }

    public function testIncrementing()
    {
        $category = new Category();
        $this->assertFalse($category->getIncrementing());
    }

    public function testKeyType()
    {
        $category = new Category();
        $this->assertEquals('string', $category->getKeyType());
    }

    public function testDates()
    {
        $category = new Category();
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        $this->assertEqualsCanonicalizing($dates, $category->getDates());
        $this->assertCount(count($dates), $category->getDates());
    }

    public function testFillable()
    {
        $category = new Category();
        $this->assertEquals(['name', 'description', 'is_active'], $category->getFillable());
    }

    public function testCasts()
    {
        $category = new Category();
        $this->assertEquals(['is_active' => 'boolean'], $category->getCasts());
    }
}
