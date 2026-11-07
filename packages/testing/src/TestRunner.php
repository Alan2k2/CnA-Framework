<?php

declare(strict_types=1);

namespace CnA\Testing;

use ReflectionClass;
use ReflectionMethod;

class TestRunner
{
    public static function runDirectory(string $directory): int
    {
        echo "\033[36mRunning CnA Test Suite in [{$directory}]...\033[0m\n\n";

        $files = static::findTestFiles($directory);
        if (empty($files)) {
            echo "No test files found in {$directory}\n";
            return 0;
        }

        $passed = 0;
        $failed = 0;
        $failures = [];

        foreach ($files as $file) {
            require_once $file;
            $classes = static::getClassesInFile($file);

            foreach ($classes as $class) {
                if (!class_exists($class)) continue;
                $reflector = new ReflectionClass($class);
                if ($reflector->isAbstract() || !$reflector->isSubclassOf(TestCase::class)) {
                    continue;
                }

                $methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);
                foreach ($methods as $method) {
                    $name = $method->getName();
                    if (!str_starts_with($name, 'test') && !str_contains($method->getDocComment() ?: '', '@test')) {
                        continue;
                    }

                    $testCase = $reflector->newInstance();

                    // Run setUp if exists
                    $setUp = $reflector->getMethod('setUp');
                    $setUp->invoke($testCase);

                    try {
                        $method->invoke($testCase);
                        echo "\033[32m.\033[0m";
                        $passed++;
                    } catch (\Throwable $e) {
                        echo "\033[31mF\033[0m";
                        $failed++;
                        $failures[] = [
                            'class' => $class,
                            'method' => $name,
                            'file' => $file,
                            'line' => $e->getLine(),
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ];
                    }

                    // Run tearDown if exists
                    $tearDown = $reflector->getMethod('tearDown');
                    $tearDown->invoke($testCase);
                }
            }
        }

        echo "\n\n";

        if (!empty($failures)) {
            echo "\033[31mFAILURES! ({$failed} failed, {$passed} passed)\033[0m\n\n";
            foreach ($failures as $i => $fail) {
                echo ($i + 1) . ") {$fail['class']}::{$fail['method']}\n";
                echo "   {$fail['error']}\n";
                echo "   at {$fail['file']}:{$fail['line']}\n\n";
            }
            return 1;
        }

        echo "\033[32mOK ({$passed} tests passed successfully)\033[0m\n";
        return 0;
    }

    protected static function findTestFiles(string $dir): array
    {
        if (!is_dir($dir)) return [];
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $item) {
            if ($item->isFile() && str_ends_with($item->getFilename(), 'Test.php')) {
                $files[] = $item->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    protected static function getClassesInFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) return [];

        $tokens = token_get_all($content);
        $classes = [];
        $namespace = '';

        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        $i = $j;
                        break;
                    }
                    $namespace .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                }
                $namespace = trim($namespace);
            }

            if ($tokens[$i][0] === T_CLASS) {
                if ($i > 0 && is_array($tokens[$i - 1]) && $tokens[$i - 1][0] === T_WHITESPACE && isset($tokens[$i - 2]) && is_array($tokens[$i - 2]) && $tokens[$i - 2][0] === T_DOUBLE_COLON) {
                    continue;
                }
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $className = $tokens[$j][1];
                        $classes[] = $namespace !== '' ? "{$namespace}\\{$className}" : $className;
                        break;
                    }
                }
            }
        }

        return $classes;
    }
}

