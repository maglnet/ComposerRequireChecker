<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Exception;

use ComposerRequireChecker\Exception\FileParseFailed;
use Exception;
use PHPUnit\Framework\TestCase;

/** @covers \ComposerRequireChecker\Exception\FileParseFailed */
final class FileParseFailedTest extends TestCase
{
    public function testPreviousExceptionMessageIsRepresented(): void
    {
        $exception = new Exception('Dummy Exception');

        $subject = new FileParseFailed('file', $exception);

        self::assertSame(0, $subject->getCode());
        self::assertSame(
            'Parsing the file [file] resulted in an error: Dummy Exception',
            $subject->getMessage(),
        );
    }
}
