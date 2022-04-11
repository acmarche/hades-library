<?php

namespace AcMarche\Pivot\Entities\Pivot;

use AcMarche\Pivot\Entities\DateBeginEnd;
use DateTimeInterface;

class Event extends Offer
{
    public DateTimeInterface $dateBegin;
    public DateTimeInterface $dateEnd;

    public bool $active;
    public string $email;
    public string $tel;

    public string|SpecData|null $description;
    public string|SpecData|null $tarif;

    public string $image;
    /**
     * @var DateBeginEnd[]
     */
    public array $dates = [];
}
