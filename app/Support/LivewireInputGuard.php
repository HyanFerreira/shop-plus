<?php

namespace App\Support;

use Livewire\Component;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

/** Reject malformed browser values before PHP coerces typed properties or action arguments. */
final class LivewireInputGuard
{
    public function update(Component $component, string $path, mixed $value): void
    {
        if (! str_starts_with($component::class, 'App\\Livewire\\')) {
            return;
        }
        $property = explode('.', $path)[0];
        if ($property === 'paginators') {
            if ($path === 'paginators') {
                abort_unless(is_array($value) && array_diff(array_keys($value), ['page']) === [], 422);
                foreach ($value as $page) {
                    $this->page($page);
                }
            } else {
                abort_unless($path === 'paginators.page', 422);
                $this->page($value);
            }

            return;
        }
        if (! property_exists($component, $property)) {
            abort(422, 'Campo inválido.');
        }
        $type = (new ReflectionProperty($component, $property))->getType();
        if (! str_contains($path, '.')) {
            // Livewire represents an emptied number input as null, then unsets
            // the typed property. Let the form's required rule report that state.
            if ($value === null && $type instanceof ReflectionNamedType && $type->getName() === 'int') {
                return;
            }
            $this->check($type, $value);
        } elseif ($type instanceof ReflectionNamedType && in_array($type->getName(), ['string', 'int', 'bool'], true)) {
            abort(422, 'Campo inválido.');
        }
    }

    public function call(Component $component, string $method, array $params): void
    {
        if (! str_starts_with($component::class, 'App\\Livewire\\') || ! method_exists($component, $method)) {
            return;
        }
        if (in_array($method, ['gotoPage', 'setPage', 'nextPage', 'previousPage', 'resetPage', 'getPage'], true)) {
            $hasPage = in_array($method, ['gotoPage', 'setPage'], true);
            if ($hasPage) {
                $this->page($params[0] ?? null);
            }
            abort_unless(($params[$hasPage ? 1 : 0] ?? 'page') === 'page', 422);
        }
        $index = 0;
        foreach ((new ReflectionMethod($component, $method))->getParameters() as $parameter) {
            $type = $parameter->getType();
            // Laravel injects action dependencies; browser parameters start after them.
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                continue;
            }
            if (array_key_exists($index, $params)) {
                $this->check($type, $params[$index++]);
            } elseif (! $parameter->isOptional()) {
                abort(422, 'Parâmetro obrigatório.');
            }
        }
        abort_if($index < count($params), 422, 'Parâmetro inesperado.');
    }

    private function check(mixed $type, mixed $value): void
    {
        if (! $type instanceof ReflectionNamedType || ! $type->isBuiltin() || ($value === null && $type->allowsNull())) {
            return;
        }
        $valid = match ($type->getName()) {
            'string' => is_string($value),
            'int' => (is_int($value) || is_string($value)) && filter_var($value, FILTER_VALIDATE_INT) !== false,
            'bool' => in_array($value, [true, false, 0, 1, '0', '1'], true),
            'array' => is_array($value),
            default => true,
        };
        abort_unless($valid, 422, 'Tipo de entrada inválido.');
    }

    private function page(mixed $value): void
    {
        abort_unless((is_int($value) || is_string($value))
            && filter_var($value, FILTER_VALIDATE_INT) !== false
            && (int) $value >= 1 && (int) $value <= 10000, 422, 'Página inválida.');
    }
}
