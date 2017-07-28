<?php

/**
 * PAYONE Magento 2 Connector is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PAYONE Magento 2 Connector is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PAYONE Magento 2 Connector. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Api\Payolution;

use Locale;

/**
 * Class for requesting the privacy declaration from payolution
 */
class PrivacyDeclaration
{
    /**
     * URL to payolution privacy declaration API
     *
     * @var string
     */
    protected $sAcceptanceBaseUrl = 'https://payment.payolution.com/payolution-payment/infoport/dataprivacydeclaration';

    /**
     * Fallback template for the case that payolution server is not reachable or it delivers an unexpected response
     *
     * @var string
     */
    protected $sFallback = "
        <header>
            <strong>Zusätzliche Hinweise für die Datenschutzerklärung für Kauf auf Rechnung, Ratenzahlung und Zahlung mittels SEPA-Basis-Lastschrift von **company** (im Folgenden: \"wir\")</strong></br>
            <span><i>(Stand: 17.03.2016)</i></span>
        </header>
        <ol>
          <li><p>Bei Kauf auf Rechnung oder Ratenzahlung oder SEPA-Basis-Lastschrift wird von Ihnen während des Bestellprozesses eine datenschutzrechtliche Einwilligung eingeholt. Folgend finden Sie eine Wiederholung dieser Bestimmungen, die lediglich informativen Charakter haben.</p></li>
          <li><p>Bei Auswahl von Kauf auf Rechnung oder Ratenzahlung oder Bezahlung mittels SEPA-Basis-Lastschrift werden für die Abwicklung dieser Zahlarten personenbezogene Daten (Vorname, Nachname, Adresse, Email, Telefonnummer, Geburtsdatum, IP-Adresse, Geschlecht) gemeinsam mit für die Transaktionsabwicklung erforderlichen Daten (Artikel, Rechnungsbetrag, Zinsen, Raten, Fälligkeiten, Gesamtbetrag, Rechnungsnummer, Steuern, Währung, Bestelldatum und Bestellzeitpunkt) an payolution übermittelt werden. payolution hat ein berechtigtes Interesse an den Daten und benötigt bzw. verwendet diese um Risikoüberprüfungen durchzuführen.</p></li>
          <li>
            <p>Zur Überprüfung der Identität bzw. Bonität des Kunden werden Abfragen und Auskünfte bei öffentlich zugänglichen Datenbanken sowie Kreditauskunfteien durchgeführt. Bei nachstehenden Anbietern können Auskünfte und gegebenenfalls Bonitätsinformationen auf Basis mathematisch-statistischer Verfahren eingeholt werden:</p>
            <ul>
                <li>CRIF GmbH, Diefenbachgasse 35, A-1150 Wien</li>
                <li>CRIF AG, Hagenholzstrasse 81, CH-8050 Zürich</li>
                <li>Deltavista GmbH, Dessauerstraße 9, D-80992 München</li>
                <li>SCHUFA Holding AG, Kormoranweg 5, D-65201 Wiesbaden</li>
                <li>KSV1870 Information GmbH, Wagenseilgasse 7, A-1120 Wien</li>
                <li>Bürgel Wirtschaftsinformationen GmbH & Co. KG, Gasstraße 18, D-22761 Hamburg</li>
                <li>Creditreform Boniversum GmbH, Hellersbergstr. 11, D-41460 Neuss</li>
                <li>infoscore Consumer Data GmbH, Rheinstraße 99, D-76532 Baden-Baden</li>
                <li>ProfileAddress Direktmarketing GmbH, Altmannsdorfer Strasse 311, A-1230 Wien</li>
                <li>Deutsche Post Direkt GmbH, Junkersring 57, D-53844 Troisdorf</li>
                <li>payolution GmbH, Am Euro Platz 2, A-1120 Wien</li>
            </ul>
            <p>payolution wird Ihre Angaben zur Bankverbindung (insbesondere Bankleitzahl und Kontonummer) zum Zwecke der Kontonummernprüfung an die SCHUFA Holding AG übermitteln. Die SCHUFA prüft anhand dieser Daten zunächst, ob die von Ihnen gemachten Angaben zur Bankverbindung plausibel sind. Die SCHUFA überprüft, ob die zur Prüfung verwendeten Daten ggf. in Ihrem Datenbestand gespeichert sind und übermittelt sodann das Ergebnis der Überprüfung an payolution zurück. Ein weiterer Datenaustausch wie die Bekanntgabe von Bonitätsinformationen oder eine Übermittlung abweichender Bankverbindungsdaten sowie Speicherung Ihrer Daten im SCHUFA-Datenbestand finden im Rahmen der Kontonummernprüfung nicht statt. Es wird aus Nachweisgründen allein die Tatsache der Überprüfung der Bankverbindungsdaten bei der SCHUFA gespeichert.</p>
            <p>payolution ist berechtigt, auch Daten zu etwaigem nicht-vertragsgemäßen Verhalten (z.B. unbestrittene offene Forderungen) zu speichern, zu verarbeiten, zu nutzen und an oben genannte Auskunfteien zu übermitteln.</p>
          </li>
          <li><p>Wir sind bereits nach den Bestimmungen des Bürgerlichen Gesetzbuches über Finanzierungshilfen zwischen Unternehmern und Verbrauchern, zu einer Prüfung Ihrer Kreditwürdigkeit gesetzlich verpflichtet.</p></li>
          <li><p>Im Fall eines Kaufs auf Rechnung oder Ratenkauf oder einer Bezahlung mittels SEPA-Basis-Lastschrift werden der payolution GmbH Daten über die Aufnahme (zu Ihrer Person, Kaufpreis, Laufzeit des Teilzahlungsgeschäfts, Ratenbeginn) und vereinbarungsgemäße Abwicklung (z.B. vorzeitige Rückzahlung, Laufzeitverlängerung, erfolgte Rückzahlungen) dieses Teilzahlungsgeschäfts übermittelt. Nach Abtretung der Kaufpreisforderung wird die forderungsübernehmende Bank die genannten Datenübermittlungen vornehmen. Wir bzw. die Bank, der die Kaufpreisforderung abgetreten wird, werden payolution GmbH auch Daten aufgrund nichtvertragsgemäßer Abwicklung (z.B. Kündigung des Teilzahlungsgeschäfts, Zwangsvollstreckungs-maßnahmen) melden. Diese Meldungen dürfen nach den datenschutzrechtlichen Bestimmungen nur erfolgen, soweit dies zur Wahrung berechtigter Interessen von Vertragspartnern der payolution GmbH oder der Allgemeinheit erforderlich ist und dadurch Ihre schutzwürdigen Belange nicht beeinträchtigt werden. payolution GmbH speichert die Daten, um ihren Vertragspartnern, die gewerbsmäßig Teilzahlungs- und sonstige Kreditgeschäfte an Verbraucher geben, Informationen zur Beurteilung der Kreditwürdigkeit von Kunden geben zu können. An Unternehmen, die gewerbsmäßig Forderungen einziehen und payolution GmbH vertraglich angeschlossen sind, können zum Zwecke der Schuldnerermittlung Adressdaten übermittelt werden. payolution GmbH stellt die Daten ihren Vertragspartnern nur zur Verfügung, wenn diese ein berechtigtes Interesse an der Datenübermittlung glaubhaft darlegen. payolution GmbH übermittelt nur objektive Daten ohne Angabe der Bank; subjektive Werturteile sowie persönliche Einkommens- und Vermögensverhältnisse sind in Auskünften der payolution GmbH nicht enthalten.</p></li>
          <li><p>Die im Bestellprozess durch Einwilligung erfolgte Zustimmung zur Datenweitergabe kann jederzeit, auch ohne Angabe von Gründen, uns gegenüber widerrufen können. Die oben genannten gesetzlichen Verpflichtungen zur Überprüfung Ihrer Kreditwürdigkeit bleiben von einem allfälligen Widerruf jedoch unberührt. Sie sind verpflichtet ausschließlich wahrheitsgetreue Angaben gegenüber uns zu machen.</p></li>
          <li><p>Sollten Sie Auskunft über die Erhebung, Nutzung, Verarbeitung oder Übermittlung von Sie betreffenden personenbezogenen Daten erhalten wollen oder Auskünfte, Berichtigungen, Sperrungen oder Löschung dieser Daten wünschen, können Sie sich an den Sachbearbeiter für Datenschutz bei payolution wenden:</p></li>
        </ol>

        <footer>Sachbearbeiter für Datenschutz<br />
            datenschutz@payolution.com<br />
            payolution GmbH<br />
            Am Euro Platz 2<br />
            1120 Wien<br />
            DVR: 4008655
        </footer>
    ";

    /**
     * PAYONE shop helper
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * Magento curl object
     *
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop $shopHelper
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->shopHelper = $shopHelper;
        $this->curl = $curl;
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
    }

    /**
     * Request acceptance text from payolution
     *
     * @param  string $sCompany
     * @return string|false
     */
    protected function getAcceptanceTextFromPayolution($sCompany)
    {
        $sUrl = $this->sAcceptanceBaseUrl.'?mId='.base64_encode($sCompany).'&lang='.$this->shopHelper->getLocale();
        $this->curl->get($sUrl);
        $sContent = $this->curl->getBody();
        $sPage = false;
        if (!empty($sContent) && stripos($sContent, 'payolution') !== false && stripos($sContent, '<header>') !== false) {
            //Parse content from HTML-body-tag from the given page
            $sRegex = "#<\s*?body\b[^>]*>(.*?)</body\b[^>]*>#s";
            preg_match($sRegex, $sContent, $aMatches);
            if (is_array($aMatches) && count($aMatches) > 1) {
                $sPage = $aMatches[1];
                //remove everything before the <header> tag ( a window.close link which wouldn't work in the given context )
                $sPage = substr($sPage, stripos($sPage, '<header>'));
            }
        }
        return $sPage;
    }

    /**
     * Get acceptance text for the given payolution payment method
     *
     * @param  string $sPaymentCode
     * @return string|false
     */
    public function getPayolutionAcceptanceText($sPaymentCode)
    {
        if ((bool)$this->shopHelper->getConfigParam('active', $sPaymentCode, 'payment') === false) {
            return false;
        }

        $sCompany = $this->shopHelper->getConfigParam('company', $sPaymentCode, 'payone_payment');
        $sPage = $this->getAcceptanceTextFromPayolution($sCompany);
        if (!$sPage) {
            $sPage = $this->getFallbackText($sCompany);
        }

        if (!$this->isUtf8($sPage)) {
            $sPage = utf8_encode($sPage);
        }

        return $sPage;
    }

    /**
     * Get fallback template
     *
     * @param  string $sCompany
     * @return mixed
     */
    protected function getFallbackText($sCompany)
    {
        return str_replace('**company**', $sCompany, $this->sFallback);
    }

    /**
     * Determine if the string is utf8 encoded
     *
     * @param  string $sString
     * @return bool
     */
    protected function isUtf8($sString)
    {
        if (preg_match('!!u', $sString)) { // this is utf-8
            return true;
        }
        return false; // definitely not utf-8
    }
}