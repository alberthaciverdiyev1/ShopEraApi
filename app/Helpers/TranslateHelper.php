<?php
namespace App\Helpers;

use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslateHelper
{

    public static function translate(string $text, string $targetLang, string $sourceLang = 'az'): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (str_word_count($text) <= 3 && !str_ends_with($text, '.')) {
            $text .= '.';
        }

        try {
            $tr = new \Stichoza\GoogleTranslate\GoogleTranslate();
            $tr->setSource($sourceLang);
            $tr->setTarget($targetLang);
            $translated = $tr->translate($text);

            $translated = rtrim($translated, '.');

            return $translated ?: $text;
        } catch (\Exception $e) {
            return $text;
        }
    }


}
