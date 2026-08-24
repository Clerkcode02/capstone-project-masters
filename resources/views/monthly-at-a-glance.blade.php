<x-app-layout>
    <!--
    THESIS: capacity status is a board, not a dashboard — refuses the hero-metric
    card grid every capacity tool ships.
    OWN-WORLD: rail-concourse split-flap board. Graphite/steel ground, condensed
    Big Shoulders numerals in fixed flap cells, ruled rows as the composition.
    Lamp state: lit/neutral = on target, amber = below threshold, red = over
    threshold — never color alone, every lamp carries a label and an icon.
    STORY: a manager scans the board, spots a lamp that needs attention, opens
    its row to a printed paper slip carrying the exact formula chain.
    FIRST VIEWPORT: readout strip of four board counters, then the row grid
    filling the frame; recalculate cascades only the rows that changed.
    FORM: split-flap concourse board, seed 0b8da7b3, won fused vs. challenger
    signals-instruments-split-flap-concourse.
    FINISH: unreviewed and undocumented is unfinished; this build ends with the
    finish review, the verdict, DESIGN.md, and every shipping raster carrying
    its provenance.
    -->
    <x-slot name="header">
        <h2 class="font-board text-2xl font-extrabold tracking-tight text-gray-800 leading-tight">
            {{ __('At-a-Glance — Monthly') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:dashboard.monthly-at-a-glance />
        </div>
    </div>
</x-app-layout>
