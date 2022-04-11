<?php

namespace AcMarche\Pivot\Parser;

use AcMarche\Pivot\Entities\Event\Event;
use AcMarche\Pivot\Entities\Hebergement\Hotel;
use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Entities\Specification\SpecEvent;
use AcMarche\Pivot\Entities\Specification\SpecInfo;
use AcMarche\Pivot\Repository\PivotRepository;
use AcMarche\Pivot\Spec\SpecTypeConst;
use AcMarche\Pivot\Spec\UrnList;
use AcMarche\Pivot\Spec\UrnUtils;

class PivotParser
{
    public function __construct(private PivotRepository $pivotRepository, private UrnUtils $urnUtils)
    {
    }

    public function parse(Offre|Event|Hotel $offre)
    {
        $eventSpec = new SpecEvent($offre->spec);
        $offre->homepage = $eventSpec->getHomePage();
        $offre->active = $eventSpec->isActive();
        foreach ($eventSpec->getByType(SpecTypeConst::EMAIL) as $spec) {
            $offre->emails[] = $spec->value;
        }
        foreach ($eventSpec->getByType(SpecTypeConst::TEL) as $spec) {
            $offre->tels[] = $spec->value;
        }
        $offre->description = $eventSpec->getByUrn(UrnList::DESCRIPTION, true);
        $offre->descriptions = $eventSpec->getByUrns(UrnList::DESCRIPTION_SHORT, true);
        //  $this->io->writeln($eventSpec->getByUrn(UrnEnum::NOMO, true));
        $offre->tarif = $eventSpec->getByUrn(UrnList::TARIF, true);
        $cats = $eventSpec->getByUrnCat(UrnList::CATEGORIE);
        foreach ($cats as $cat) {
            $info = $this->urnUtils->getInfosUrn($cat->urn);
            if ($info) {
                $offre->categories[] = $info->labelByLanguage('fr');
            }
        }
    }

    /**
     * Complète la class Event
     * Date de début, date de fin,...
     * @param array $events
     */
    public function parseEvents(array $events): void
    {
        array_map(function ($event) {
            $this->parseEvent($event);
        }, $events);
    }

    /**
     * @param Event $event
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function parseEvent(Event $event)
    {
        foreach ($event->spec as $spec) {
            $event->specsDetailed[] = new SpecInfo($this->urnUtils->getInfosUrn($spec->urn), $spec);
        }

        $this->parse($event);
        $eventSpec = new SpecEvent($event->spec);
        $datesValidite = $eventSpec->dateBeginAndEnd();
        $event->dates = $eventSpec->getDates();
        $event->dateBegin = $datesValidite[0];
        $event->dateEnd = $datesValidite[1];
        $this->parseRelOffre($event);
    }

    public function parseRelOffre($event)
    {
        if (is_array($event->relOffre)) {
            foreach ($event->relOffre as $relation) {
                //dump($relation);
                $item = $relation->offre;
                $code = $item['codeCgt'];
                $idType = $item['typeOffre']['idTypeOffre'];
                $sOffre = $this->pivotRepository->offreByCgt($code, $item['dateModification']);
                if ($sOffre) {
                    $itemSpec = new SpecEvent($sOffre->getOffre()->spec);
                    if ($image = $itemSpec->getByUrn(UrnList::URL)) {
                        $event->images[] = $image->value;
                    }
                }
            }
        }
    }

    /**
     * @param Hotel[] $hotels
     * @return void
     */
    public function parseHotels(array $hotels)
    {
        array_map(function ($hotel) {
            $this->parseHotel($hotel);
        }, $hotels);
    }

    /**
     * @param Event $hotel
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function parseHotel(Hotel $hotel)
    {
        foreach ($hotel->spec as $spec) {
            $hotel->specsDetailed[] = new SpecInfo($this->urnUtils->getInfosUrn($spec->urn), $spec);
        }
        $this->parse($hotel);
        $this->parseRelOffre($hotel);
    }
}
