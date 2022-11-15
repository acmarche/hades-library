<?php

namespace AcMarche\Pivot\Parser;

use AcMarche\Pivot\Entities\Event\DateBeginEnd;
use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Spec\UrnList;
use AcMarche\Pivot\Utils\DateUtils;
use AcMarche\Pivot\Utils\SortUtils;

trait ParserEventTrait
{
    public function dateBeginAndEnd(Offre $offre): array
    {
        $dates = [];
        $format = "d/m/Y";
        $dateDebut = $this->findByUrn($offre, UrnList::DATE_DEB_VALID->value, returnData: true);
        if (count($dateDebut) > 0) {
            $dates[] = DateUtils::convertStringToDateTime($dateDebut[0]->value, $format);
        }

        $dateFin = $this->findByUrn($offre, UrnList::DATE_FIN_VALID->value, returnData: true);
        if (count($dateFin) > 0) {
            $dates[] = DateUtils::convertStringToDateTime($dateFin[0]->value, $format);
        }

        return $dates;
    }

    /**
     * @return DateBeginEnd[]
     */
    public function getDates(Offre $offre): array
    {
        $dates = [];
        $specs = $this->findByUrn($offre, UrnList::DATE_OBJECT->value, returnData: true);
        foreach ($specs as $spec) {
            foreach ($spec->spec as $data) {
                if ($data->urn == UrnList::DATE_DEB->value) {
                    $dateBegin = $data->value;
                }
                if ($data->urn == UrnList::DATE_DEB->value) {
                    $dateEnd = $data->value;
                }
            }
            $dates[] = new DateBeginEnd($dateBegin, $dateEnd);
        }
        $dates = SortUtils::sortDatesEvent($dates);

        return $dates;
    }
}
