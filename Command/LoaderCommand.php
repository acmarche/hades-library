<?php

namespace AcMarche\Pivot\Command;

use AcMarche\Pivot\Entities\Pivot\Result\ResultOfferDetail;
use AcMarche\Pivot\Entities\Pivot\Result\TypeOffreResult;
use AcMarche\Pivot\Pivot;
use AcMarche\Pivot\Repository\PivotRemoteRepository;
use AcMarche\Pivot\Utils\FileUtils;
use AcMarche\Pivot\Utils\SerializerPivot;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

class LoaderCommand extends Command
{
    protected static $defaultName = 'pivot:loadxml';
    private SymfonyStyle $io;
    private PivotRemoteRepository $pivotRemoteRepository;
    private SerializerInterface $serializer;

    public function __construct(string $name = null)
    {
        $this->serializer = SerializerPivot::create();
        $this->pivotRemoteRepository = new PivotRemoteRepository();
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Charge le xml de hades');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $query = file_get_contents('/var/www/visit/AcMarche/Pivot/Query/test.xml');
        var_dump($this->pivotRemoteRepository->queryPost($query));

        return Command::SUCCESS;
    }

    private function detailOffre()
    {
        $hotel = 'HTL-01-08GR-01AY';
        $hotelString = $this->pivotRemoteRepository->offreByCgt($hotel);
        dump($this->serializer->deserialize($hotelString, ResultOfferDetail::class, 'json'));
    }

    private function getTypes()
    {
        $jsonString = $this->pivotRemoteRepository->getThesaurus(Pivot::THESAURUS_TYPE_OFFRE);
        $titi = $this->serializer->deserialize($jsonString, TypeOffreResult::class, 'json', [

        ]);
        foreach ($titi->spec as $type) {
            var_dump($type);
            break;
            //echo $type['code'];
        }
        //   var_dump($serializer->deserialize(file_get_contents('/var/www/visit/one.json'), TypeOffre::class, 'json'));
        //echo($jsonString);
    }

    private function getLastSync(): array
    {
        if (is_readable(FileUtils::FILE_NAME_LOG)) {
            try {
                return explode(',', file_get_contents(FileUtils::FILE_NAME_LOG));
            } catch (\Exception $exception) {

            }
        }

        return [];

        //calling :
        $today = date('Y-m-d');

        $result = $this->getLastSync();
        if ($result['date'] == $today && $result['result'] == 'true') {
            return Command::SUCCESS;
        }

        return [];
    }

}