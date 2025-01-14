<?php

namespace Hexlet\Code;

class Validator
{
    public function validateUrl(array $data): array
    {
        $errors = [];
        $url = $data['name'] ?? '';

        if (empty($url)) {
            $errors['name'] = 'URL не должен быть пустым';
        } elseif (strlen($url) > 255 || !filter_var($url, FILTER_VALIDATE_URL)) {
            $errors['name'] = 'Некорректный URL';
        }

        return $errors;
    }
}