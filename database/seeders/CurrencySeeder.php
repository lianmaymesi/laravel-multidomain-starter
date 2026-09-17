<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * ISO 4217 code => [name, symbol, decimal_digits]. Only USD ships
     * active + primary — everything else is inactive until an admin opts
     * in from the backoffice, same pattern as Language rows.
     *
     * @var array<string, array{0: string, 1: ?string, 2: int}>
     */
    private const CURRENCIES = [
        'USD' => ['US Dollar', '$', 2],
        'EUR' => ['Euro', '€', 2],
        'GBP' => ['British Pound', '£', 2],
        'INR' => ['Indian Rupee', '₹', 2],
        'JPY' => ['Japanese Yen', '¥', 0],
        'CNY' => ['Chinese Yuan', '¥', 2],
        'AUD' => ['Australian Dollar', '$', 2],
        'CAD' => ['Canadian Dollar', '$', 2],
        'CHF' => ['Swiss Franc', 'Fr', 2],
        'SGD' => ['Singapore Dollar', '$', 2],
        'HKD' => ['Hong Kong Dollar', '$', 2],
        'NZD' => ['New Zealand Dollar', '$', 2],
        'AED' => ['UAE Dirham', 'د.إ', 2],
        'SAR' => ['Saudi Riyal', '﷼', 2],
        'QAR' => ['Qatari Riyal', '﷼', 2],
        'KWD' => ['Kuwaiti Dinar', 'د.ك', 3],
        'BHD' => ['Bahraini Dinar', '.د.ب', 3],
        'OMR' => ['Omani Rial', '﷼', 3],
        'JOD' => ['Jordanian Dinar', 'د.ا', 3],
        'EGP' => ['Egyptian Pound', 'E£', 2],
        'ZAR' => ['South African Rand', 'R', 2],
        'NGN' => ['Nigerian Naira', '₦', 2],
        'KES' => ['Kenyan Shilling', 'KSh', 2],
        'BRL' => ['Brazilian Real', 'R$', 2],
        'MXN' => ['Mexican Peso', '$', 2],
        'RUB' => ['Russian Ruble', '₽', 2],
        'TRY' => ['Turkish Lira', '₺', 2],
        'KRW' => ['South Korean Won', '₩', 0],
        'THB' => ['Thai Baht', '฿', 2],
        'IDR' => ['Indonesian Rupiah', 'Rp', 2],
        'MYR' => ['Malaysian Ringgit', 'RM', 2],
        'PHP' => ['Philippine Peso', '₱', 2],
        'VND' => ['Vietnamese Dong', '₫', 0],
        'PKR' => ['Pakistani Rupee', '₨', 2],
        'BDT' => ['Bangladeshi Taka', '৳', 2],
        'NPR' => ['Nepalese Rupee', '₨', 2],
        'LKR' => ['Sri Lankan Rupee', '₨', 2],
        'SEK' => ['Swedish Krona', 'kr', 2],
        'NOK' => ['Norwegian Krone', 'kr', 2],
        'DKK' => ['Danish Krone', 'kr', 2],
        'PLN' => ['Polish Zloty', 'zł', 2],
        'CZK' => ['Czech Koruna', 'Kč', 2],
        'HUF' => ['Hungarian Forint', 'Ft', 2],
        'ILS' => ['Israeli Shekel', '₪', 2],
    ];

    public function run(): void
    {
        $order = 1;

        foreach (self::CURRENCIES as $code => [$name, $symbol, $decimals]) {
            Currency::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'symbol' => $symbol,
                    'decimal_digits' => $decimals,
                    'is_primary' => $code === 'USD',
                    'is_active' => $code === 'USD',
                    'exchange_rate' => $code === 'USD' ? 1 : null,
                    'order' => $order++,
                ],
            );
        }
    }
}
