<?php

namespace KiisKepri\Esign\Support;

trait HandlesDates
{
    protected function parseDate(?string $date, ?string $formatV1 = null): ?\DateTimeImmutable
    {
        if ($date === null || $date === '') {
            return null;
        }

        if (str_contains($date, 'T')) {
            try {
                return new \DateTimeImmutable($date);
            } catch (\Exception) {
                return null;
            }
        }

        if ($formatV1 !== null) {
            $dt = \DateTimeImmutable::createFromFormat($formatV1, $date, new \DateTimeZone('Asia/Jakarta'));
            return $dt instanceof \DateTimeImmutable ? $dt : null;
        }

        try {
            return new \DateTimeImmutable($date);
        } catch (\Exception) {
            return null;
        }
    }
}
