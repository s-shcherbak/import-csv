<?php

declare(strict_types=1);

namespace App\Service\Utils;

use Symfony\Component\Console\Output\OutputInterface;

interface ProductImportInterface
{
    /**
     * @param void
     *
     * @return int
     */
    public function getRowCount(): int;

    /**
     * @param void
     *
     * @return int
     */
    public function getRowValidCount(): int;

    /**
     * @param void
     *
     * @return int
     */
    public function getRowErrorCount(): int;

    /**
     * @param void
     *
     * @return array
     */

    public function execute(string $filePath, OutputInterface $output, bool $testMode): bool;
}