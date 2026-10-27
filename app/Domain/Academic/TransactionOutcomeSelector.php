<?php

namespace App\Domain\Academic;

use InvalidArgumentException;

class TransactionOutcomeSelector
{
    public function choose(): string
    {
        return $this->forDraw(random_int(1, 100));
    }

    public function forDraw(int $draw): string
    {
        $weights = config('academic-automation.probabilities');
        if (! is_array($weights) || array_keys($weights) !== ['approved', 'declined', 'pending']
            || count(array_filter($weights, fn ($weight) => is_int($weight) && $weight >= 0 && $weight <= 100)) !== 3
            || array_sum($weights) !== 100 || $draw < 1 || $draw > 100) {
            throw new InvalidArgumentException('Probabilidades acadêmicas devem ser inteiros de 0 a 100, somando 100; sorteio entre 1 e 100.');
        }

        $limit = 0;
        foreach ($weights as $outcome => $weight) {
            $limit += $weight;
            if ($draw <= $limit) {
                return $outcome;
            }
        }

        throw new InvalidArgumentException('Configuração acadêmica inválida.');
    }
}
