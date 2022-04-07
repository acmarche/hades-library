<?php

namespace AcMarche\Pivot\Command;

use AcMarche\Pivot\Entities\Person;
use AcMarche\Pivot\Entities\Pivot\Response\ResponseQuery;
use AcMarche\Pivot\Entities\Pivot\Response\ResultOfferDetail;
use AcMarche\Pivot\Entities\Pivot\Response\TypeOffreResult;
use AcMarche\Pivot\Filtre\PivotFilter;
use AcMarche\Pivot\Repository\PivotRemoteRepository;
use AcMarche\Pivot\Repository\PivotRepository;
use AcMarche\Pivot\Spec\SpecEvent;
use AcMarche\Pivot\Spec\SpecTypeConst;
use AcMarche\Pivot\Spec\UrnConst;
use AcMarche\Pivot\Thesaurus;
use AcMarche\Pivot\Utils\FileUtils;
use AcMarche\Pivot\Utils\GenerateClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Serializer\DependencyInjection\SerializerPass;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class LoaderCommand extends Command
{
    protected static $defaultName = 'pivot:loadxml';
    private SymfonyStyle $io;

    public function __construct(
        private SerializerInterface $serializer,
        private PivotRepository $pivotRepository,
        private PivotRemoteRepository $pivotRemoteRepository,
        private GenerateClass $generateClass,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Charge le xml de hades');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //$this->generateClass->generateTypeUrn();
        // echo($this->pivotRemoteRepository->getThesaurus(Thesaurus::THESAURUS_TYPE_OFFRE));
        $this->io = new SymfonyStyle($input, $output);

        $this->all();
        //$this->detailOffre();

        //$this->events($this->pivotRepository->getEvents());

        return Command::SUCCESS;
    }

    private function events(array $events)
    {
        /**
         * @var ResultOfferDetail $resultOfferDetail
         *
         * $resultOfferDetail =
         * $this->serializer->deserialize(
         * file_get_contents('/var/www/intranet/output/event.json'),
         * ResultOfferDetail::class,
         * 'json',
         * [
         * DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS,
         * ]
         * );
         * $offre = $resultOfferDetail->getOffre();
         */
        foreach ($events as $offre) {
            $this->io->writeln($offre->codeCgt);
            $this->io->writeln($offre->nom);
            $this->io->writeln($offre->typeOffre->labelByLanguage());
            $address = $offre->adresse1;
            $this->io->writeln(" ".$address->localiteByLanguage());
            $this->io->writeln(" ".$address->communeByLanguage());
            $eventSpec = new SpecEvent($offre->spec);
            $dates = $eventSpec->dateBeginAndEnd();
            if (count($dates) > 0) {
                $this->io->writeln(" ".$dates[0]->format('d-m-Y').' => '.$dates[1]->format('d-m-Y'));
            }
            $this->io->writeln(" ".$eventSpec->getHomePage());
            $this->io->writeln(" ".$eventSpec->isActive());
            $this->display($eventSpec->getByType(SpecTypeConst::EMAIL));
            $this->display($eventSpec->getByType(SpecTypeConst::TEL));
            $this->io->writeln(" ".$eventSpec->getByUrn(UrnConst::DESCRIPTION, true));
            //  $this->io->writeln($eventSpec->getByUrn(UrnEnum::NOMO, true));
            $this->io->writeln(" ".$eventSpec->getByUrn(UrnConst::TARIF, true));
            foreach ($offre->relOffre as $relation) {
                dump($relation);
                $item = $relation->offre;
                $code = dump($item['codeCgt']);
                $idType = $item['typeOffre']['idTypeOffre'];
                dump($idType);
                $sOffre = $this->pivotRepository->offreByCgt($code, $item['dateModification']);
                $itemSpec = new SpecEvent($sOffre->getOffre()->spec);
                dump($itemSpec->getByUrn(UrnConst::URL));
                dump($sOffre->getOffre()->nom);
            }
        }
    }

    private function initDi()
    {
        $containerBuilder = new ContainerBuilder();
        $loader = new PhpFileLoader(
            $containerBuilder,
            new FileLocator(__DIR__.'/../../config')
        );

        //    $loader->load('services.php');
        dump($containerBuilder->getServiceIds());

        $containerBuilder->addCompilerPass(new SerializerPass());
        //$containerBuilder->set(SerializerInterface::class, service(Serializer::class));
        // $containerBuilder->register(SerializerInterface::class, Serializer::class);
        $containerBuilder->compile();
        dump($containerBuilder->getServiceIds());

        $serializer = $containerBuilder->get(Serializer::class);
    }

    private function test(SerializerInterface $serializer)
    {
        $person = new Person();
        $person->setName('foo');
        $person->setAge(99);
        $person->setSportsperson(false);
        $json = json_encode($person);
        var_dump($json);
        $jsonContent = $serializer->deserialize($json, Person::class, 'json');
        dump($jsonContent);
    }

    private function detailOffre()
    {
        $codeCgt = 'HTL-01-08GR-01AY';
        $codeCgt = 'EVT-01-0B0S-Q0K3';
        $hotelString = $this->pivotRemoteRepository->offreByCgt($codeCgt);
        echo $hotelString;
        //dump($this->serializer->deserialize($hotelString, ResultOfferDetail::class, 'json'));
    }

    private function all()
    {
        //http://pivot.tourismewallonie.be/index.php/9-pivot-gest-pc/142-types-de-fiches-pivot
        $hotelString = $this->pivotRemoteRepository->query();
        echo $hotelString;

        return;
        $responseQuery = $this->serializer->deserialize($hotelString, ResponseQuery::class, 'json');
        $offresShort = PivotFilter::filterByType($responseQuery, 9);
        foreach ($offresShort as $offreShort) {
            echo $offreShort->codeCgt."\n";
        }
    }

    private function getTypes()
    {
        $jsonString = $this->pivotRemoteRepository->getThesaurus(Thesaurus::THESAURUS_TYPE_OFFRE);
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

    private function display(array $specs)
    {
        foreach ($specs as $spec) {
            if ($spec) {
                $this->io->writeln(" ".$spec->value);
            }
        }
    }
}
