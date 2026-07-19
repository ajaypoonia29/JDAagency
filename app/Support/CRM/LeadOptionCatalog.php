<?php

declare(strict_types=1);

namespace App\Support\CRM;

final class LeadOptionCatalog
{
    /**
     * @return array<string, string>
     */
    public static function industries(
        ?string $current = null,
    ): array {
        return self::withCurrentValue([
            'Advertising & Marketing' =>
                'Advertising & Marketing',
            'Agriculture & Agribusiness' =>
                'Agriculture & Agribusiness',
            'Automotive' => 'Automotive',
            'Banking & Financial Services' =>
                'Banking & Financial Services',
            'Beauty & Wellness' =>
                'Beauty & Wellness',
            'Construction & Infrastructure' =>
                'Construction & Infrastructure',
            'Consulting & Professional Services' =>
                'Consulting & Professional Services',
            'Education & Training' =>
                'Education & Training',
            'E-commerce & Retail' =>
                'E-commerce & Retail',
            'Food & Beverage' =>
                'Food & Beverage',
            'Healthcare & Pharmaceuticals' =>
                'Healthcare & Pharmaceuticals',
            'Hospitality & Travel' =>
                'Hospitality & Travel',
            'Information Technology & Software' =>
                'Information Technology & Software',
            'Legal Services' =>
                'Legal Services',
            'Logistics & Transportation' =>
                'Logistics & Transportation',
            'Manufacturing' =>
                'Manufacturing',
            'Media & Entertainment' =>
                'Media & Entertainment',
            'Nonprofit & Social Enterprise' =>
                'Nonprofit & Social Enterprise',
            'Real Estate & Property' =>
                'Real Estate & Property',
            'Telecommunications' =>
                'Telecommunications',
            'Other' => 'Other',
        ], $current);
    }

    /**
     * @return array<string, string>
     */
    public static function businessTypes(
        ?string $current = null,
    ): array {
        return self::withCurrentValue([
            'Sole Proprietorship' =>
                'Sole Proprietorship',
            'Partnership' => 'Partnership',
            'Limited Liability Partnership' =>
                'Limited Liability Partnership (LLP)',
            'Private Limited Company' =>
                'Private Limited Company',
            'Public Limited Company' =>
                'Public Limited Company',
            'One Person Company' =>
                'One Person Company (OPC)',
            'Startup' => 'Startup',
            'Agency' => 'Agency',
            'Freelancer / Consultant' =>
                'Freelancer / Consultant',
            'Nonprofit / NGO / Trust' =>
                'Nonprofit / NGO / Trust',
            'Government / Public Sector' =>
                'Government / Public Sector',
            'Other' => 'Other',
        ], $current);
    }

    /**
     * @return array<string, string>
     */
    public static function leadSources(
        ?string $current = null,
    ): array {
        return self::withCurrentValue([
            'Google Search' =>
                'Google Search (Organic)',
            'Google Ads' => 'Google Ads',
            'Website' =>
                'Website / Contact Form',
            'Self Generated' =>
                'Self-generated / Prospecting',
            'Referral' => 'Referral',
            'Existing Customer' =>
                'Existing Customer',
            'Social Media' => 'Social Media',
            'Facebook / Instagram Ads' =>
                'Facebook / Instagram Ads',
            'LinkedIn' => 'LinkedIn',
            'WhatsApp' => 'WhatsApp',
            'Phone / Walk-in' =>
                'Phone / Walk-in',
            'Event / Exhibition' =>
                'Event / Exhibition',
            'Partner / Channel' =>
                'Partner / Channel',
            'Marketplace / Directory' =>
                'Marketplace / Directory',
            'Email Campaign' =>
                'Email Campaign',
            'Other' => 'Other',
        ], $current);
    }

    /**
     * @return array<string, string>
     */
    public static function companySizes(
        ?string $current = null,
    ): array {
        return self::withCurrentValue([
            '1-10' => '1–10 employees',
            '11-50' => '11–50 employees',
            '51-200' => '51–200 employees',
            '201-500' => '201–500 employees',
            '501-1000' => '501–1,000 employees',
            '1001-5000' => '1,001–5,000 employees',
            '5001-10000' => '5,001–10,000 employees',
            '10001+' => '10,001+ employees',
        ], $current);
    }

    /**
     * @return array<string, string>
     */
    public static function designations(
        ?string $current = null,
    ): array {
        return self::withCurrentValue([
            'Owner' => 'Owner',
            'Founder' => 'Founder',
            'Co-Founder' => 'Co-Founder',
            'Chief Executive Officer' =>
                'Chief Executive Officer (CEO)',
            'Managing Director' =>
                'Managing Director',
            'Director' => 'Director',
            'Chief Technology Officer' =>
                'Chief Technology Officer (CTO)',
            'Chief Financial Officer' =>
                'Chief Financial Officer (CFO)',
            'Chief Operating Officer' =>
                'Chief Operating Officer (COO)',
            'Chief Marketing Officer' =>
                'Chief Marketing Officer (CMO)',
            'Vice President' =>
                'Vice President',
            'General Manager' =>
                'General Manager',
            'Head of Department' =>
                'Head of Department',
            'Sales Manager' =>
                'Sales Manager',
            'Marketing Manager' =>
                'Marketing Manager',
            'Operations Manager' =>
                'Operations Manager',
            'Purchase / Procurement Manager' =>
                'Purchase / Procurement Manager',
            'IT Manager' => 'IT Manager',
            'HR Manager' => 'HR Manager',
            'Consultant' => 'Consultant',
            'Other' => 'Other',
        ], $current);
    }

    /**
     * The stored value represents the selected budget ceiling.
     *
     * @return array<string, string>
     */
    public static function estimatedValues(
        float|int|string|null $current = null,
    ): array {
        $options = [
            '0' => 'Not estimated yet',
            '30000' => 'Up to ₹30,000',
            '50000' => 'Up to ₹50,000',
            '70000' => 'Up to ₹70,000',
            '100000' => 'Up to ₹1,00,000',
            '150000' => 'Up to ₹1,50,000',
            '200000' => 'Up to ₹2,00,000',
            '300000' => 'Up to ₹3,00,000',
            '500000' => 'Up to ₹5,00,000',
            '1000000' => 'Up to ₹10,00,000',
            '2500000' => 'Up to ₹25,00,000',
            '5000000' => 'Up to ₹50,00,000',
            '10000000' => 'Up to ₹1,00,00,000',
        ];

        if (
            $current === null
            || $current === ''
            || ! is_numeric($current)
        ) {
            return $options;
        }

        $key = self::numericKey($current);

        if (! array_key_exists($key, $options)) {
            $options[$key] = sprintf(
                'Custom existing value: ₹%s',
                number_format(
                    (float) $current,
                    2,
                ),
            );
        }

        return $options;
    }

    /**
     * @param array<string, string> $options
     * @return array<string, string>
     */
    private static function withCurrentValue(
        array $options,
        ?string $current,
    ): array {
        $value = trim((string) $current);

        if (
            $value !== ''
            && ! array_key_exists($value, $options)
        ) {
            $options[$value] =
                $value . ' (Existing value)';
        }

        return $options;
    }

    private static function numericKey(
        float|int|string $value,
    ): string {
        return rtrim(
            rtrim(
                number_format(
                    (float) $value,
                    2,
                    '.',
                    '',
                ),
                '0',
            ),
            '.',
        );
    }
}