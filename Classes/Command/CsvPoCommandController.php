<?php
namespace Sitegeist\LostInTranslation\CsvPO\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\CsvPO\Domain\TranslationOverrideRepository;
use Sitegeist\CsvPO\Domain\TranslationOverride;
use Sitegeist\CsvPO\Domain\TranslationLabelSource;
use Sitegeist\CsvPO\Domain\TranslationLabelSourceRepository;
use Neos\Flow\I18n\Service as LocalizationService;
use Neos\Flow\I18n\Locale;
use Sitegeist\LostInTranslation\Domain\TranslationServiceInterface;

class CsvPoCommandController extends CommandController
{
    /**
     * @var TranslationOverrideRepository
     * @Flow\Inject
     */
    protected $translationOverrideRepository;

    /**
     * @var TranslationLabelSourceRepository
     * @Flow\Inject
     */
    protected $translationLabelSourceRepository;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="management.locales")
     */
    protected $locales;

    /**
     * @var LocalizationService
     * @Flow\Inject
     */
    protected $localizationService;

    /**
     * @var TranslationServiceInterface
     * @Flow\Inject
     */
    protected $lostInTranslationService;

    /**
     * Add missing translations for all translation sources
     *
     * @param string $source Locale identifier of the source language
     * @param string $target Locale identifier of the target language
     * @param bool $force Force translation of all labels
     * @param string|null $deeplSource Source language identifier for DeepL, falls back to $source if not defined
     * @param string|null $deeplTarget Target language identifier for DeepL, falls back to $target if not defined
     */
    public function translateAllCommand(string $source, string $target, bool $force = false, ?string $deeplSource = null , ?string $deeplTarget = null) {
        $allTranslationSources = $this->translationLabelSourceRepository->findAll();
        foreach ($allTranslationSources as $translationSource) {
            $this->translateCommand($translationSource->getIdentifier(), $source, $target, $force, $deeplSource, $deeplTarget);
        }
    }

    /**
     * Add missing translations for the given translation source
     *
     * @param string $identifier The translation source identifier (aka the resource://filename of the csv file)
     * @param string $source Locale identifier of the source language
     * @param string $target Locale identifier of the target language
     * @param bool $force Force translation of all labels
     * @param string|null $deeplSource Source language identifier for DeepL, falls back to $source if not defined
     * @param string|null $deeplTarget Target language identifier for DeepL, falls back to $target if not defined
     */
    public function translateCommand(string $identifier, string $source, string $target,  bool $force = false, ?string $deeplSource = null , ?string $deeplTarget = null) {

        if ($source == $target) {
            $this->output->outputLine("Source and target have to be different!");
            $this->quit();
        }

        $translationSource = $this->translationLabelSourceRepository->findOneByIdentifier($identifier);

        if (!$translationSource) {
            $this->output->outputLine("Translation source could not be found.");
            $this->quit();
        }

        $this->output->outputLine(sprintf("Translations: %s" , $identifier));
        $this->output->outputLine();

        $labelsToTranslate = $this->findLabelsToTranslate($translationSource, $source, $target, $force);

        if (count($labelsToTranslate) == 0) {
            return;
        }

        $translatedLabels = $this->lostInTranslationService->translate(
            $labelsToTranslate,
            $deeplTarget ?? $target,
            $deeplSource ?? $source
        );

        $this->createOverridesForTranslationLabels($identifier, $target, $translatedLabels);

        foreach($translatedLabels as $id => $label) {
            $this->output->outputLine(sprintf('<info>%s</info> in locale <info>%s</info> is translated as: <info>%s</info>', $id, $target, $label));
        }
        $this->output->outputLine();
    }

    /**
     * @param TranslationLabelSource $translationSource
     * @param string $sourceLanguageIdentifier
     * @param string $targetLanguagIdentifier
     * @param bool $force
     * @return array<string,string>
     * @throws \Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException
     */
    protected function findLabelsToTranslate(TranslationLabelSource $translationSource, string $sourceLanguageIdentifier, string $targetLanguagIdentifier, bool $force): array
    {
        $labelsToTranslate = [];
        foreach ($translationSource->findAllTranslationLabels() as $translationLabel) {
            $sourceLocaleChain = $this->localizationService->getLocaleChain(new Locale($sourceLanguageIdentifier));
            $sourceTranslationLabel = $translationLabel->findTranslationForLocaleChain($sourceLocaleChain);

            if (empty((string)$sourceTranslationLabel)) {
                continue;
            }

            if ($force) {
                $labelsToTranslate[$translationLabel->getIdentifier()] = (string)$sourceTranslationLabel;
            } else {
                $targetLocaleChain = $this->localizationService->getLocaleChain(new Locale($targetLanguagIdentifier));
                $targetTranslation = $translationLabel->findTranslationForLocaleChain($targetLocaleChain);

                if (!$targetTranslation->getOverride() && !$targetTranslation->getTranslation()) {
                    $labelsToTranslate[$translationLabel->getIdentifier()] = (string)$sourceTranslationLabel;
                }
            }
        }
        return $labelsToTranslate;
    }

    /**
     * @param string $translationSourceIdentifier
     * @param string $localeIdentifier
     * @param array<string,string> $labels
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    protected function createOverridesForTranslationLabels(string $translationSourceIdentifier, string $localeIdentifier, array $labels): void
    {
        foreach ($labels as $labelIdentifier => $translatedLabel) {
            $override = $this->translationOverrideRepository->findOneSpecific($translationSourceIdentifier, $localeIdentifier, $labelIdentifier);
            if ($override) {
                $override->setTranslation($translatedLabel);
                $this->translationOverrideRepository->update($override);
            } else {
                $override = new TranslationOverride();
                $override->setSourceIdentifier($translationSourceIdentifier);
                $override->setLabelIdentifier($labelIdentifier);
                $override->setLocaleIdentifier($localeIdentifier);
                $override->setTranslation($translatedLabel);
                $this->translationOverrideRepository->add($override);
            }
        }
    }
}
