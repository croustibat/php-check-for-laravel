<?php

it('will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

it('src namespace is correct')
    ->expect('Croustibat\ComposerCheck')
    ->toBeClasses();
