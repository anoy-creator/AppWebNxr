<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** php bin/console app:make-puml */
#[AsCommand(
    name: 'app:make-puml',
    description: 'Génère un diagramme PlantUML des entités Doctrine'
)]
final class PumlCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $puml = "@startuml\n\n";
        $puml .= "title Diagramme BDD - Entités Doctrine\n\n";
        $puml .= "skinparam classAttributeIconSize 0\n";
        $puml .= "hide methods\n";
        $puml .= "hide circle\n\n";

        foreach ($metadata as $classMetadata) {
            $entityName = $this->shortName($classMetadata->getName());

            $puml .= "class {$entityName} {\n";

            foreach ($classMetadata->getFieldNames() as $fieldName) {
                $type = $classMetadata->getTypeOfField($fieldName);
                $puml .= "  {$fieldName}: {$type}\n";
            }

            foreach ($classMetadata->getAssociationNames() as $associationName) {
                $targetEntity = $this->shortName($classMetadata->getAssociationTargetClass($associationName));
                $puml .= "  {$associationName}: {$targetEntity}\n";
            }

            $puml .= "}\n\n";
        }

        foreach ($metadata as $classMetadata) {
            $sourceEntity = $this->shortName($classMetadata->getName());

            foreach ($classMetadata->associationMappings as $association) {
                $targetEntity = $this->shortName($association['targetEntity']);
                $relation = $this->relationLabel($association['type']);

                $puml .= "{$sourceEntity} {$relation} {$targetEntity} : {$association['fieldName']}\n";
            }
        }

        $puml .= "\n@enduml\n";

        $outputDir = 'puml';

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        file_put_contents($outputDir.'/index.puml', $puml);

        $encoded = $this->encodePlantUml($puml);
        $url = 'https://www.plantuml.com/plantuml/png/'.$encoded;

        $command = sprintf(
            'curl --silent %s --output %s',
            escapeshellarg($url),
            escapeshellarg($outputDir.'/index.png')
        );

        system($command);

        $output->writeln('');
        $output->writeln('<info>✅ Diagramme généré avec succès</info>');
        $output->writeln('');
        $output->writeln('<comment>Fichiers créés :</comment>');
        $output->writeln(' - puml/index.puml');
        $output->writeln(' - puml/index.png');
        $output->writeln('');
        $output->writeln('<comment>Commande à lancer :</comment>');
        $output->writeln(' php bin/console app:make-puml');
        $output->writeln('');

        return Command::SUCCESS;
    }

    private function shortName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }

    private function relationLabel(int $type): string
    {
        return match ($type) {
            ClassMetadata::ONE_TO_ONE => '"1" -- "1"',
            ClassMetadata::ONE_TO_MANY => '"1" -- "*"',
            ClassMetadata::MANY_TO_ONE => '"*" -- "1"',
            ClassMetadata::MANY_TO_MANY => '"*" -- "*"',
            default => '--',
        };
    }

    private function encodePlantUml(string $text): string
    {
        return $this->encode64(gzdeflate($text, 9));
    }

    private function encode64(string $data): string
    {
        $result = '';
        $length = strlen($data);

        for ($i = 0; $i < $length; $i += 3) {
            if ($i + 2 === $length) {
                $result .= $this->append3Bytes(
                    ord($data[$i]),
                    ord($data[$i + 1]),
                    0
                );
            } elseif ($i + 1 === $length) {
                $result .= $this->append3Bytes(
                    ord($data[$i]),
                    0,
                    0
                );
            } else {
                $result .= $this->append3Bytes(
                    ord($data[$i]),
                    ord($data[$i + 1]),
                    ord($data[$i + 2])
                );
            }
        }

        return $result;
    }

    private function append3Bytes(int $b1, int $b2, int $b3): string
    {
        $c1 = $b1 >> 2;
        $c2 = (($b1 & 0x3) << 4) | ($b2 >> 4);
        $c3 = (($b2 & 0xF) << 2) | ($b3 >> 6);
        $c4 = $b3 & 0x3F;

        return
            $this->encode6Bit($c1 & 0x3F).
            $this->encode6Bit($c2 & 0x3F).
            $this->encode6Bit($c3 & 0x3F).
            $this->encode6Bit($c4 & 0x3F);
    }

    private function encode6Bit(int $value): string
    {
        if ($value < 10) {
            return chr(48 + $value);
        }

        $value -= 10;

        if ($value < 26) {
            return chr(65 + $value);
        }

        $value -= 26;

        if ($value < 26) {
            return chr(97 + $value);
        }

        $value -= 26;

        return match ($value) {
            0 => '-',
            1 => '_',
            default => '?',
        };
    }
}
