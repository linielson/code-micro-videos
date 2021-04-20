<?php
declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Foundation\Testing\TestResponse;

trait TestValidations
{
  protected function assertInvalidationFields(TestResponse $res, array $fields, string $rule, array $rulesParams = [])
  {
    $res
      ->assertStatus(422)
      ->assertJsonValidationErrors($fields);

    foreach ($fields as $field) {
      $fieldName = str_replace('_', ' ', $field);
      $res->assertJsonFragment([
        \Lang::get("validation.{$rule}", ['attribute' => $fieldName] + $rulesParams)
      ]);
    }
  }
}
