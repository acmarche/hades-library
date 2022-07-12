<?php

namespace AcMarche\Pivot\Command;

use AcMarche\Pivot\Entity\TypeOffre;
use AcMarche\Pivot\Parser\ParserEventTrait;
use AcMarche\Pivot\Repository\PivotRepository;
use AcMarche\Pivot\Repository\TypeOffreRepository;
use AcMarche\Pivot\Spec\SpecTrait;
use AcMarche\Pivot\Spec\UrnUtils;
use AcMarche\Pivot\Utils\GenerateClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pivot:types-offre',
    description: 'Génère une table avec tous les types d\'offres',
)]
class TypeOffreCommand extends Command
{
    use SpecTrait, ParserEventTrait;

    private SymfonyStyle $io;
    private OutputInterface $output;

    public function __construct(
        private GenerateClass $generateClass,
        private PivotRepository $pivotRepository,
        private TypeOffreRepository $typeOffreRepository,
        private UrnUtils $urnUtils,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addOption('flush', "flush", InputOption::VALUE_NONE, 'Enregistrer dans la DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->output = $output;

        $this->createListing();
        $flush = (bool)$input->getOption('flush');
        if ($flush) {
            $this->typeOffreRepository->flush();
        }

        return Command::SUCCESS;
    }

    private function createListing()
    {
        $families = $this->pivotRepository->getFamilies();

        foreach ($families as $family) {
            $this->io->section($family->labelByLanguage('fr'));
            $root = new TypeOffre(
                $family->labelByLanguage('fr'),
                $family->order,
                $family->value,
                $family->urn,
                $family->type,
                null
            );
            $this->typeOffreRepository->persist($root);
            foreach ($family->spec as $child) {
                $this->io->writeln($child->labelByLanguage('fr'));
                $childObject = new TypeOffre(
                    $child->labelByLanguage('fr'),
                    $child->order,
                    $child->value,
                    $child->urn,
                    $child->type,
                    $root
                );
                $this->treatmentChild($childObject);
            }
        }
    }

    private function treatmentChild(TypeOffre $typeOffre): TypeOffre
    {
        if (!$this->typeOffreRepository->findByUrn($typeOffre->urn)) {
            $this->typeOffreRepository->persist($typeOffre);
        }

        return $typeOffre;
    }
}
