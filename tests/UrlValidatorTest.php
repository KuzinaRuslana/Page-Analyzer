<?php

namespace Hexlet\Code\Tests;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\UrlValidator;

class UrlValidatorTest extends TestCase
{
    private UrlValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UrlValidator();
    }

    public function testValidateUrlEmpty()
    {
        $data = ['name' => ''];
        $errors = $this->validator->validateUrl($data);

        $this->assertArrayHasKey('name', $errors);
        $this->assertSame('URL не должен быть пустым', $errors['name']);
    }

    public function testValidateUrlInvalid()
    {
        $data = ['name' => 'www.some-url'];
        $errors = $this->validator->validateUrl($data);

        $this->assertArrayHasKey('name', $errors);
        $this->assertSame('Некорректный URL', $errors['name']);
    }

    public function testValidateUrlTooLong()
    {
        $longUrl = str_repeat('some-url', 100);
        $data = ['name' => $longUrl];
        $errors = $this->validator->validateUrl($data);

        $this->assertArrayHasKey('name', $errors);
        $this->assertSame('Некорректный URL', $errors['name']);
    }

    public function testValidateUrlWithoutNameKey()
    {
        $data = [];
        $errors = $this->validator->validateUrl($data);

        $this->assertArrayHasKey('name', $errors);
        $this->assertSame('URL не должен быть пустым', $errors['name']);
    }
}
