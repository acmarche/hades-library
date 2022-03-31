<?php

namespace AcMarche\Pivot\Command;

use AcMarche\Pivot\Entities\Person;
use AcMarche\Pivot\Entities\Pivot\Response\ResponseQuery;
use AcMarche\Pivot\Entities\Pivot\Response\TypeOffreResult;
use AcMarche\Pivot\Filtre\PivotFilter;
use AcMarche\Pivot\Pivot;
use AcMarche\Pivot\Repository\PivotRemoteRepository;
use AcMarche\Pivot\Utils\FileUtils;
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
    private PivotRemoteRepository $pivotRemoteRepository;

    public function __construct(private SerializerInterface $serializer, string $name = null)
    {
        $this->pivotRemoteRepository = new PivotRemoteRepository(Pivot::FORMAT_JSON);
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

        $this->all();

        return Command::SUCCESS;
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
        $hotel = 'HTL-01-08GR-01AY';
        $hotelString = $this->pivotRemoteRepository->offreByCgt($hotel);
        echo $hotelString;
        //dump($this->serializer->deserialize($hotelString, ResultOfferDetail::class, 'json'));
    }

    private function all()
    {
        //http://pivot.tourismewallonie.be/index.php/9-pivot-gest-pc/142-types-de-fiches-pivot
        $hotelString = $this->pivotRemoteRepository->query();
        $responseQuery = $this->serializer->deserialize($hotelString, ResponseQuery::class, 'json');
        $offresShort = PivotFilter::filterByType($responseQuery, 9);
        foreach ($offresShort as $offreShort) {
            echo $offreShort->codeCgt."\n";
        }
        //echo $hotelString;
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
