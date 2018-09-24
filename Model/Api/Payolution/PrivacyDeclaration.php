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
            <strong>Ergänzende Hinweise zur Datenschutzerklärung für Kauf auf Rechnung, per Ratenzahlung und direkter SEPA-Lastschrift von Test GmbH (im Folgenden: „wir“)</strong></br>
            <span><i>(Stand: 08.05.2018)</i></span>
        </header>
        <ol>
            <li><p>Durch die Auswahl eines Kaufs auf Rechnung, per Ratenzahlung oder direkter SEPA-Lastschrift, stimmen Sie den Datenschutzbestimmungen der payolution GmbH und der Weiterverarbeitung Ihrer persönlichen Daten zu. Diese Bestimmungen sind nachstehend ausschließlich zu Informationszwecken erneut aufgeführt.</p></li>
            <li><p>Wenn Sie die Zahlung auf Rechnung, per Ratenzahlung oder direkter SEPA-Lastschrift auswählen, werden Ihre für die Bearbeitung dieser Zahlungsmethode erforderlichen persönlichen Informationen (Vorname, Nachname, Anschrift, E-Mail-Adresse, Telefonnummer, Geburtsdatum, IP-Adresse, Geschlecht) zusammen mit den für die Ausführung der Transaktion erforderlichen Daten (Artikel, Rechnungsbetrag, Zinsen, Ratenzahlungen, Fälligkeitsdatum, Gesamtbetrag, Rechnungsnummer, Steuerbetrag, Währung, Bestelldatum und -uhrzeit) an die payolution GmbH zum Zwecke der Risikoeinschätzung im Rahmen seiner regulatorischen Verpflichten weitergeleitet.</p></li>
            <li>
                <p>Zur Identitäts- und/oder Solvenzprüfung des Kunden werden Abfragen und Auskunftsersuchen an öffentlich zugängliche Datenbanken und Kreditauskunfteien weitergeleitet. Es können Informationen, und falls erforderlich, Kreditauskünfte auf Grundlage statistischer Methoden bei den folgenden Anbietern abgefragt werden:</p>
                <ul>
                    <li>CRIF GmbH, Diefenbachgasse 35, 11 50 Wien, Österreich</li>
                    <li>CRIF AG, Hagenholzstrasse 81, 8050 Zürich, Schweiz</li>
                    <li>CRIF Bürgel GmbH, Radlkoferstraße 2, 81373 München, Deutschland</li>
                    <li>SCHUFA Holding AG, Kormoranweg 5, 65201 Wiesbaden, Deutschland</li>
                    <li>KSV1870 Information GmbH, Wagenseilgasse 7, 1120 Wien, Österreich</li>
                    <li>Creditreform Boniversum GmbH, Hellersbergstr. 11, 41460 Neuss, Deutschland</li>
                    <li>infoscore Consumer Data GmbH, Rheinstrasse 99, 76532 Baden-Baden, Deutschland</li>
                    <li>ProfileAddress Direktmarketing GmbH, Altmannsdorfer Strasse 311, 1230 Wien, Österreich</li>
                    <li>Emailage LTD, 1 Fore Street Ave, London, EC2Y 5EJ, Vereinigtes Königreich</li>
                    <li>ThreatMetrix, Inc.,160 W Santa Clara St., Suite 1400, San Jose, CA 95113, USA</li>
                    <li>payolution GmbH, Am Euro Platz 2, 1120 Wien, Österreich</li>
                </ul>
                <p>Die payolution GmbH wird Ihre Angaben zur Bankverbindung (insbesondere Bankleitzahl und Kontonummer) zum Zwecke der Kontonummernprüfung an die SCHUFA Holding AG übermitteln. Die SCHUFA prüft anhand dieser Daten zunächst, ob die von Ihnen gemachten Angaben zur Bankverbindung plausibel sind. Die SCHUFA überprüft, ob die zur Prüfung verwendeten Daten ggf. in Ihrem Datenbestand gespeichert sind und übermittelt sodann das Ergebnis der Überprüfung an payolution zurück. Ein weiterer Datenaustausch wie die Bekanntgabe von Bonitätsinformationen oder eine Übermittlung abweichender Bankverbindungsdaten sowie Speicherung Ihrer Daten im SCHUFA-Datenbestand finden im Rahmen der Kontonummernprüfung nicht statt. Es wird aus Nachweisgründen allein die Tatsache der Überprüfung der Bankverbindungsdaten bei der SCHUFA gespeichert.</p>
                <p>Im Fall von vertragswidrigem Verhalten (z. B. Bestehen unstrittiger Forderungen) ist die payolution GmbH ebenfalls zur Speicherung, Verarbeitung, Verwendung von Daten und deren Übermittlung an die o. g. Kreditauskunfteien berechtigt.</p>
            </li>
            <li><p>Gemäß den Bestimmungen des Bürgerlichen Gesetzbuches über Finanzierungshilfen zwischen Händlern und Konsumenten sind wir gesetzlich zur Prüfung Ihrer Kreditwürdigkeit verpflichtet.</p></li>
            <li><p>Im Falle eines Kaufs auf Rechnung, per Ratenzahlung oder direkter SEPA-Lastschrift, werden wir Daten zu den Einzelheiten des entsprechenden Zahlungsvorgangs (Ihre Personendaten, Kaufpreis, Bedingungen des Zahlungsvorgangs, Beginn der Zahlung) und die Vertragsbedingungen (z. B. vorzeitige Zahlung, Verlängerung der Vertragslaufzeit, erfolgte Zahlungen) an die payolution GmbH übermitteln. Nach Zuweisung der Kaufpreisforderung wird das Bankinstitut, dem die Forderung zugewiesen wurde, die genannte Datenübermittlung vornehmen. Wir und/oder das Bankinstitut sind entsprechend der Zuweisung der Kaufpreisforderung ebenfalls zur Meldung von Daten über vertragswidriges Verhalten (z. B. Beendigung der Zahlungsvereinbarung, Zwangsvollstreckungsmaßnahmen) an die payolution GmbH angewiesen. Gemäß den Datenschutzbestimmungen erfolgen diese Meldungen ausschließlich, wenn diese zur Sicherstellung des rechtmäßigen Interesses der Vertragspartner der payolution GmbH oder der Allgemeinheit erforderlich sind und Ihre rechtmäßigen Interessen dadurch nicht beeinträchtigt werden. Die payolution GmbH wird die Daten speichern, um seinen Vertragspartnern, die Konsumenten Ratenzahlungen oder sonstige Kreditvereinbarungen im gewerblichen Rahmen gewähren, Informationen zur Einschätzung der Kreditwürdigkeit von Kunden zur Verfügung stellen zu können. Mit der payolution GmbH in einem Vertragsverhältnis stehende gewerbliche Inkassounternehmen können Adressinformationen zur Ermittlung von Debitoren zur Verfügung gestellt werden. Die payolution GmbH ist dazu angehalten, seinen Vertragspartnern nur dann Daten zu übermitteln, wenn ein glaubwürdiges und rechtmäßiges Interesse an der Datenübermittlung besteht. Die payolution GmbH ist dazu angehalten, ausschließlich objektive Daten ohne Spezifikation an das entsprechende Bankinstitut zu übermitteln. Informationen über subjektive Werteinschätzungen und persönliches Einkommen sind in den von der payolution GmbH zur Verfügung gestellten Informationen nicht enthalten.</p></li>
            <li><p>Sie können Ihre Zustimmung zur Datenverarbeitung zum Zwecke der Auftragsabwicklung jederzeit widerrufen. Die o. g. gesetzlichen Verpflichtungen zur Prüfung Ihrer Kreditwürdigkeit bleiben von solchen Widerrufen unberührt.</p></li>
            <li><p>Sie sind uns gegenüber zur Angabe von ausschließlich wahrheitsgemäßen und korrekten Informationen verpflichtet.</p></li>
            <li><p>Weitere Informationen über die Verarbeitung Ihrer persönlichen Daten finden Sie in der vollständigen Datenschutzrichtlinie hier: <a href=\"https://www.paysafe.com/legal-and-compliance/privacy-policy/\">https://www.paysafe.com/legal-and-compliance/privacy-policy/</a></p></li>
            <li><p>Sie können ebenfalls den Sachbearbeiter für Datenschutz der Paysafe Group unter der folgenden Adresse kontaktieren:</p></li>
        </ol>
        
        <footer>
            datenschutz@payolution.com<br />
            payolution GmbH<br />
            Am Euro Platz 2<br />
            1120 Wien<br />
            Registrierungscode – Datenverarbeitung (DVR): 4008655
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