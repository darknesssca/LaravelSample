<?php


namespace App\Traits\Cache;


trait CacheTagsTrait
{
    protected function getGuidesMarksTag()
    {
        return 'guide|marks';
    }

    protected function getGuidesModelsTag()
    {
        return 'guide|models';
    }

    protected function getGuidesCategoriesTag()
    {
        return 'guide|categories';
    }

    protected function getGuidesCountriesTag()
    {
        return 'guide|countries';
    }

    protected function getGuidesGendersTag()
    {
        return 'guide|genders';
    }

    protected function getGuidesDocTypesTag()
    {
        return 'guide|doc_types';
    }

    protected function getGuidesUsageTargetsTag()
    {
        return 'guide|usage_targets';
    }

    protected function getGuidesInsuranceCompaniesTag()
    {
        return 'guide|insurance_companies';
    }

    protected function getGuidesSourceAcquisitionsTag()
    {
        return 'guide|source_acquisitions';
    }

}
