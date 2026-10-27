@php($localTime = $date->copy()->timezone('America/Sao_Paulo'))
<time datetime="{{ $localTime->toIso8601String() }}">{{ $localTime->format('d/m/Y H:i:s') }}</time>
