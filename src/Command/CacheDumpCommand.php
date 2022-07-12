<?php

namespace AcMarche\Pivot\Command;

use AcMarche\Pivot\Entities\Family\Families;
use AcMarche\Pivot\Entities\Family\Family;
use AcMarche\Pivot\Entities\Specification\SpecData;
use AcMarche\Pivot\Entity\TypeOffre;
use AcMarche\Pivot\Parser\PivotSerializer;
use AcMarche\Pivot\Repository\PivotRemoteRepository;
use AcMarche\Pivot\Repository\PivotRepository;
use AcMarche\Pivot\Spec\UrnList;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'pivot:cache-dump',
    description: 'Télécharge toutes les offres et les mets en cache',
)]
class CacheDumpCommand extends Command
{
    public function __construct(
        private PivotRepository $pivotRepository,
        private PivotRemoteRepository $pivotRemoteRepository,
        private PivotSerializer $pivotSerializer,
        private SerializerInterface $serializer,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $families = $this->pivotRepository->getFamilies();

        foreach ($families as $family) {
            $io->section($family->type);
            $io->section($family->labelByLanguage('fr'));
            foreach ($family->spec as $child) {
                $io->writeln($child->labelByLanguage('fr'));
            }
        }

        return Command::SUCCESS;
        // $offres = $this->pivotRepository->findByUrn(UrnList::PATRIMOINE_NATUREL);

        $args = [new TypeOffre("Patrimoine", 11, "urn:typ:11", null)];
        $offres = $this->pivotRepository->getOffres($args);

        foreach ($offres as $offre) {
            if ($offre->codeCgt == 'ALD-01-099R-0001') {
                $io->writeln($offre->nom);
                print_r($offre);
                break;
            }
        }


        return Command::SUCCESS;
    }
}
