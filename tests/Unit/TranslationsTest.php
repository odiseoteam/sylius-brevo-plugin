<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class TranslationsTest extends TestCase
{
    private const DIR = __DIR__ . '/../../translations';

    /** @return iterable<string, array{string}> */
    public static function translationFiles(): iterable
    {
        foreach (glob(self::DIR . '/*.yaml') ?: [] as $file) {
            if (!str_ends_with($file, '.en.yaml')) {
                yield basename($file) => [$file];
            }
        }
    }

    #[DataProvider('translationFiles')]
    public function testEveryLocaleHasTheEnglishKeysAndPlaceholders(string $file): void
    {
        $domain = explode('.', basename($file))[0];
        $english = self::flatten(self::parse(self::DIR . "/$domain.en.yaml"));
        $translated = self::flatten(self::parse($file));

        self::assertSame(array_keys($english), array_keys($translated));
        foreach ($english as $key => $message) {
            self::assertSame(self::placeholders($message), self::placeholders($translated[$key]), $key);
        }
    }

    /** @return array<array-key, mixed> */
    private static function parse(string $file): array
    {
        $messages = Yaml::parseFile($file);
        self::assertIsArray($messages);

        return $messages;
    }

    /**
     * @param array<array-key, mixed> $messages
     *
     * @return array<string, string>
     */
    private static function flatten(array $messages, string $prefix = ''): array
    {
        $flat = [];
        foreach ($messages as $key => $value) {
            $path = $prefix . $key;
            if (is_array($value)) {
                $flat += self::flatten($value, $path . '.');
            } else {
                self::assertIsString($value, $path);
                $flat[$path] = $value;
            }
        }

        return $flat;
    }

    /** @return list<string> */
    private static function placeholders(string $message): array
    {
        preg_match_all('/%[a-z_]+%|\{\{ ?[a-z_]+ ?\}\}/', $message, $matches);
        sort($matches[0]);

        return $matches[0];
    }
}
