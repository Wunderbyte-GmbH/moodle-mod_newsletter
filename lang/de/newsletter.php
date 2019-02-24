<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'newsletter', language 'de', branch 'MOODLE_30_STABLE'
 *
 * @package   newsletter
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['account_already_confirmed'] = 'Ihr Account wurde bereits aktiviert. Um zum Newsletter zu gelangen, klicken Sie bitte auf folgenden Link:  {$a->newsletterlink}.';
$string['account_confirmed'] = 'Willkommen bei {$a->sitename}, {$a->fullname}!

Ihr Account {$a->username} wurde aktiviert.
Um Ihre Profildetails zu bearbeiten, klicken Sie bitte auf folgenden Link: {$a->editlink}.
Um zum Newsletter zu gelangen, klicken Sie bitte auf folgenden Link:  {$a->newsletterlink}.';
$string['allowguestusersubscriptions_help'] = 'Erlauben Sie Gastnutzer/innen, Newsletter auf dieser Seite zu abonnieren. Dazu ist es erforderlich, dass Sie das Anlegen von Gastzugängen in den Moodleeinstellungen erlauben.';
$string['allowguestusersubscriptions'] = 'Gasteinschreibungen erlauben';
$string['allusers'] = 'Nutzer/innen (inklusive Abgemeldete):';
$string['already_published'] = 'Die Ausgabe wurde veröffentlicht.';
$string['attachments'] = 'Anhänge';
$string['attachments_help'] = 'Laden Sie hier Dateien hoch, die Sie als Anhang mit dieser Ausgabe versenden möchten.';
$string['attachments_no'] = 'Keine Anhänge vorhanden.';
$string['cohortmanagement'] = 'Globale Gruppen an/abmelden';
$string['cohortsavailable'] = 'Verfügbare Globale Gruppen';
$string['config_activation_timeout_desc'] = 'Wählen Sie hier, für wie viele Tage der per Email versendete Aktivierungslink gültig sein soll.';
$string['config_activation_timeout_label'] = 'Ablaufzeitpunkt für Aktivierungslinks';
$string['config_bounce_email'] = 'Die E-Mail-Adresse an die die Bounces geschickt werden sollen. Benutzen Sie diese E-Mailadresse ausschließlich zum Bounce Handling.';
$string['config_bounce_enable'] = 'Bounce Processing für das Newslettermodul aktivieren';
$string['config_bounceinfo'] = 'Bounce Handling  für Newslettermodul nur dann verwenden, wenn Moodle VERP Bounce Einstellungen wie auf der folgenden Seite beschrieben nicht aktiviert werden können:
https://docs.moodle.org/dev/Email_processing
Die Verwendung der VERP Moodle Methode ist nicht auf jedem System möglich und die Einrichtung kompliziert. Dies ist eine simplere Alternative, die aber nur für das Newslettermodul funktioniert. Nach Speichern der Einstellungen testen Sie diese unter {$a}';
$string['config_bounceprocessing'] = 'Einstellungen für Bounce Handling: Geben Sie hier die Logindaten der Bounce-Mailadresse an.';
$string['config_debug_desc'] = 'Aktivieren Sie diese Checkbox, um den Debug-Output im Cronjob anzuzeigen.';
$string['config_debug_label'] = 'Cron Debug Modus';
$string['config_host'] = 'Mailserver (ex. mail.yourserver.com)';
$string['config_password'] = 'Mailbox Passwort';
$string['config_port'] = 'Der Port über den Sie sich mit der Mailbox verbinden; Standard: 143, andere gängige Möglichkeiten sind 110 (POP3), 995 (Gmail)';
$string['config_send_notifications_desc'] = 'Aktivieren Sie diese Checkbox, um das Senden von Abonnement-spezifischen Benachrichtigungen an Abonnent/inn/en einzuschalten.';
$string['config_send_notifications_label'] = 'Benachrichtigungen senden';
$string['config_service'] = 'zu benutzendes Protokoll';
$string['config_service_option'] = 'Verschlüsselung (keine, tls, notls, ssl)';
$string['config_username'] = 'Mailbox Benutzername';
$string['create_new_issue'] = 'Neue Newsletter-Ausgabe erstellen';
$string['default_stylesheet'] = 'Standard-Stylesheet';
$string['delete_all_subscriptions'] = 'Alle Abonnements löschen';
$string['delete_issue'] = 'Diese Newsletter-Ausgabe löschen';
$string['delete_issue_question'] = 'Sind Sie sicher, dass Sie diese Newsletter-Ausgabe löschen wollen?';
$string['delete_subscription_question'] = 'Sind Sie sicher, dass Sie dieses Newsletter-Abonnement kündigen wollen?';
$string['edit_issue'] = 'Diese Ausgabe bearbeiten';
$string['edit_issue_title'] = 'Newsletter-Ausgabe bearbeiten';
$string['edit_subscription_title'] = 'Abonnement bearbeiten';
$string['emailexists'] = 'Es existiert bereits ein Benutzeraccount mit dieser E-Mailadresse, eine erneute Anlage ist daher nicht nötig. Bitte loggen Sie sich ein, um diesen Newsletter zu abonnieren.

Sollten Sie Ihre Zugangsdaten vergessen haben, benutzen Sie bitte den {$a} Link auf der Login-Seite um Ihr Passwort zurücksetzen zu lassen.';
$string['entries_per_page'] = 'Angezeigte Einträge pro Seite';
$string['eventissuecreated'] = 'Newsletter-Ausgabe wurde erstellt';
$string['eventissueviewed'] = 'Newsletter-Ausgabe wurde angezeigt';
$string['eventsubscriptioncreated'] = 'Neues Newsletter-Abonnement';
$string['eventsubscriptiondeleted'] = 'Newsletter-Abonnement gelöscht';
$string['eventsubscriptionresubscribed'] = 'Wiederanmeldung zum Newsletter';
$string['eventsubscriptionsviewed'] = 'Newsletter-Abonnements angezeigt';
$string['eventsubscriptionunsubscribed'] = 'Abmeldung von Newsletter';
$string['filteredusers'] = 'Gefilterte Nutzer/innen: ';
$string['groupby'] = 'Ausgaben gruppieren nach:';
$string['guestsubscribe'] = 'Abonnieren Sie jetzt!';
$string['guestsubscriptionsuccess'] = 'Ihre E-Mailadresse wurde erfolgreich registriert. <br /> Um Ihr Abonnement zu bestätigen,  überprüfen Sie bitte Ihre Mailbox ({$a}) und klicken Sie auf den darin enthaltenen Bestätigungslink.';
$string['header_actions'] = 'Aktionen';
$string['header_content'] = 'Inhalt der Newsletter-Ausgabe';
$string['header_email'] = 'E-Mail';
$string['header_health'] = 'Status (Gesendet / Retouren)';
$string['header_bounceratio'] = 'Retourenverhältnis';
$string['header_name'] = 'Name';
$string['header_publish'] = 'Veröffentlichungsoptionen';
$string['header_publishinfo'] = 'Hat die Veröffentlichung einer Newsletter-Ausgabe einmal begonnen, kann man das Veröffentlichungsdatum nicht mehr ändern.';
$string['header_subscriberid'] = 'Angemeldet von';
$string['header_timestatuschanged'] = 'Letzte Statusänderung';
$string['header_timesubscribed'] = 'Abonnement-Anmeldedatum';
$string['header_unsubscriberid'] = 'Abgemeldet von';
$string['health_0'] = 'Aktiv';
$string['health_1'] = 'Problematisch';
$string['health_2'] = 'Blacklisted';
$string['health_4'] = 'Abgemeldet';
$string['issue_htmlcontent'] = 'HTML Inhalt';
$string['issue_stylesheet'] = 'Stylesheet-Datei für HTML Inhalt';
$string['issue_title'] = 'Ausgabentitel';
$string['issue_title_help'] = 'Geben Sie hier den Titel der Ausgabe ein (erforderlich).';
$string['manage_subscriptions'] = 'Abonnements verwalten';
$string['mode_group_by_month'] = 'Ausgaben nach Erscheinungsmonat gruppieren';
$string['mode_group_by_week'] = 'Ausgaben nach Erscheinungswoche gruppieren';
$string['mode_group_by_year'] = 'Ausgaben nach Erscheinungsjahr gruppieren';
$string['modulename'] = 'Newsletter';
$string['modulename_help'] = 'Das Newslettermodul ermöglicht das Veröffentlichen von E-Mail-Newslettern.';
$string['modulenameplural'] = 'Newsletter';
$string['newsletter'] = 'Newsletter';
$string['newsletter:addinstance'] = 'Newsletter hinzufügen';
$string['newsletter:createissue'] = 'Eine neue Newsletter-Ausgabe erstellen';
$string['newsletter:deleteissue'] = 'Eine Newsletter-Ausgabe löschen';
$string['newsletter:deletesubscription'] = 'Newsletter-Abonnements löschen';
$string['newsletter:editissue'] = 'Eine Newsletter-Ausgabe bearbeiten';
$string['newsletter:editsubscription'] = 'Newsletter-Abonnements bearbeiten';
$string['newsletterintro'] = 'Beschreibung';
$string['newsletter:manageownsubscription'] = 'Mein Newsletter-Abonnement verwalten';
$string['newsletter:managesubscriptions'] = 'Newsletter-Abonnements verwalten';
$string['newslettername'] = 'Name';
$string['newslettername_help'] = 'Dies ist der Inhalt des Hilfe Werkzeugtipps für das Newsletterfeld. Markdown-Syntax wird unterstützt.';
$string['newsletter:publishissue'] = 'Eine Newsletter-Ausgabe veröffentlichen';
$string['newsletter:readissue'] = 'Eine Newsletter-Ausgabe lesen';
$string['newsletter:subscribecohort'] = 'Globale Gruppe für den Newsletter anmelden';
$string['newsletter:subscribeuser'] = 'Nutzer/innen für den Newsletter anmelden';
$string['newsletter:unsubscribecohort'] = 'Globale Gruppe vom Newsletter abmelden';
$string['newsletter:viewnewsletter'] = 'Newsletter-Instanz anzeigen';
$string['new_user_subscribe_message'] = 'Hallo {$a->fullname},

Es wurde mit Ihrer E-Mail-Adresse ein neues Konto bei \'Moodle Übersetzung\' angefordert.

Anmeldename: {$a->username}
Passwort: {$a->password}

Um das neue Konto zu bestätigen, gehen Sie bitte zu dieser Web-Adresse:

{$a->link}

In den meisten Mail-Programmen sollte diese als anklickbarer Link angezeigt werden.
Sollte dies nicht der Fall sein, kopieren Sie bitte diesen Link und fügen Sie ihn in die Adressleiste Ihres Web-Browser ein.

Wenn Sie Hilfe benötigen, kontaktieren Sie bitte den/die Administrator/in der Website,
{$a->admin}';
$string['no_issues'] = 'Zu diesem Newsletter bestehen noch keine Ausgaben.';
$string['send_newsletter'] = 'Newsletter versenden';
$string['process_bounces'] = 'Zurückgewiesene E-Mails verarbeiten';
$string['page_first'] = 'Erste Seite';
$string['page_last'] = 'Letzte Seite';
$string['page_next'] = 'Nächste';
$string['page_previous'] = 'Vorherige';
$string['pluginadministration'] = 'Newsletter Administration';
$string['pluginname'] = 'Newsletter';
$string['publish_in'] = 'Veröffentlichung in {$a->days} Tagen, {$a->hours} Stunden, {$a->minutes} Minuten, {$a->seconds} Sekunden';
$string['publishon'] = 'Veröffentlichen am';
$string['resubscribe'] = 'Wiederanmeldung bestätigen';
$string['resubscribe_text'] = 'Sie wurden für diesen Newsletter abgemeldet. Möchten Sie sich wirkich wieder anmelden?';
$string['resubscribe_btn'] = 'Anmeldung bestätigen';
$string['resubscriptionsuccess'] = 'Ihre neuerliche Registrierung war erfolgreich.';
$string['stylesheets'] = 'Newsletter-Stylesheets hochladen';
$string['stylesheets_help'] = 'Laden Sie hier CSS-Dateien hoch, die als Stylesheets für die Ausgaben des Newsletters Verwendung finden sollen. Sie können mehr als eine Datei hochladen und anschließend aus diesen wählen, wenn Sie neue neue Ausgabe erstellen. Dieses Feld ist optional, da das Modul bereits mit mindestens einer vorhandenen Stylesheet-Datei ausgestattet ist.';
$string['sub_mode_forced'] = 'Verpflichtend (automatisches Abo ohne Abmeldemöglichkeit)';
$string['sub_mode_opt_in'] = 'Opt-in (Abo muss durch Nutzer/in iniitiert werden)';
$string['sub_mode_opt_out'] = 'Opt-out (Automatisches Abonnement, Abmeldung durch Nutzer/in möglich)';
$string['subscribe'] = 'Abonnieren';
$string['subscribedusers'] = 'Abonnent/inn/en';
$string['subscribedusersmatching'] = 'Passende Abonnent/inn/en für Suchkriterium ({$a})';
$string['subscribe_question'] = 'Möchten Sie den Newsletter "{$a->name}" unter Verwendung der E-Mailadresse "{$a->email}" abonnieren?';
$string['subscribercandidates'] = 'Mögliche Abonnent/inn/en';
$string['subscribercandidatesmatching'] = 'Passende Nutzer/innen für ({$a})';
$string['subscription_mode'] = 'Abonnementeinstellung';
$string['subscription_mode_help'] = 'Wählen Sie aus, ob eingeschriebene Nutzer/innen für diesen Newsletter automatisch (opt-out) angemeldet werden, oder sie sich manuell anmelden müssen (opt-in). WARNUNG: Opt-out bedeutet die automatische Anmeldung ALLER Nutzer/innen des Kontexts. Bei auf der Startseite angelegten Newslettern bedeutet dies ein Abo aller NutzerInnen der gesamten Moodle-Plattform!';
$string['toc'] = 'Wie die Inhaltsangabe generiert werden soll';
$string['toc_help'] = 'Bis zu welcher Stufe sollen Überschriften inkludiert werden? Beispiel: Sie haben eine Ausgabe mit einer drei-stufigen Überschriftenstruktur (h1, h2, h3). Wenn Sie aber nur die Stufen 1 und 2 (h1 und h2) in die Inhaltsangabe einbeziehen möchten, wählen Sie die "2". Wenn Sie nur die erste Stufe einbezogen haben möchten, wählen Sie die "1".';
$string['toc_header'] = 'Inhaltsangabe';
$string['toc_no'] = 'Keine Inhaltsangabe generieren';
$string['toc_yes'] = 'Eine {$a}-stufige Inhaltsangabe generieren';
$string['unsubscribe'] = 'Dieses Newsletter-Abonnement kündigen';
$string['unsubscribedinfo'] = 'Mit (!) markierte Nutzer/innen sind abgemeldet';
$string['unsubscribe_link_text'] = 'Klicken Sie hier, um das Abonnement zu kündigen';
$string['unsubscribe_question'] = 'Möchten Sie das Abonnement der E-Mailadresse "{$a->email}" für den Newsletter "{$a->name}" wirklich kündigen?';
$string['unsubscription_succesful'] = 'Ihr Abonnement mit der E-Mailadresse "{$a->email}" wurde für den folgenden Newsletter erfolgreich gekündigt: "{$a->name}"';
$string['unsubscribe_mail_subj'] = 'Sie wurden erfolgreich vom Newsletter abgemeldet';
$string['unsubscribe_mail_text'] = '<p>
Dear {$a->firstname} {$a->lastname},
<br>
You were successfully unsubscribed from the newsletter {$a->newslettertitle}. If you did this on purpose, there is nothing more to do. If you did accidentally unsubscribe, you can resubscribe now under the following link:
<br>
{$a->newsletterurl}</p>';
$string['unsubscribe_nounsub_text'] = 'Link zur Newsletter-Abokündigung nicht senden';
$string['unsubscribe_nounsub'] = 'Distributor';
$string['welcomemessage'] = 'Willkommens-Nachricht';
$string['welcomemessage_help'] = 'Geben Sie hier die Nachricht an, die dem neuen Abonnenten nach seiner Anmeldung zu einem Newsletter angezeigt werden soll.';
$string['welcomemessageguestuser'] = 'Willkommens-Nachricht Gastuser-Anmeldung';
$string['welcomemessageguestuser_help'] = 'Geben Sie hier die Nachricht an, die einem Gastuser nach seiner Anmeldung zu einem Newsletter angezeigt werden soll.';
$string['welcometonewsletter'] = 'Vielen Dank! Sie erhalten von nun ab diesen Newsletter per E-Mail.';
$string['welcometonewsletter_guestsubscription'] = 'Vielen Dank! Sie erhalten von nun ab diesen Newsletter per E-Mail.<br />Sie können sich von diesem Newsletter wieder abmelden, wenn Sie den Link "Dieses Newsletter-Abonnement kündigen" nach dem Login anklicken oder mittels Klick auf den "Abmelden"-Link in jeder Ausgabe dieses Newsletters.';


// Privacy API.
$string['privacy:metadata:newsletter_subscriptions'] = 'Newsletter-Abo anzeigen';
$string['privacy:metadata:newsletter_subscriptions:userid'] = 'Nutzer/in, die das Abo initiert hat.';
$string['privacy:metadata:newsletter_subscriptions:newsletterid'] = 'ID des abonnierten Newsletters';
$string['privacy:metadata:newsletter_subscriptions:health'] = 'Status der abgewiesenen Newsletter';
$string['privacy:metadata:newsletter_subscriptions:timesubscribed'] = 'Zeitpunk des Abo-Beginns';
$string['privacy:metadata:newsletter_subscriptions:timestatuschanged'] = 'Letzte Änderung';
$string['privacy:metadata:newsletter_subscriptions:subscriberid'] = 'ID des/der Nutzer/in, die das Newsletter-Abo erhalten hat';
$string['privacy:metadata:newsletter_subscriptions:unsubscriberid'] = 'ID des/der Nutzer/in, die das Abo gekündigt hat';
$string['privacy:metadata:newsletter_subscriptions:sentnewsletters'] = 'Anzahl der an die Person gesendeten Newsletter';
$string['privacy:metadata:newsletter_bounces'] = 'Newsletter deren Zustellung verweigert wurde';
$string['privacy:metadata:newsletter_bounces:userid'] = 'Nutzer/in die/der den Eintrag erstellt hat';
$string['privacy:metadata:newsletter_bounces:issueid'] = 'Newsletterausgabe, die zurückgewiesen wurde';
$string['privacy:metadata:newsletter_bounces:statuscode'] = 'Statuscode der Zurückweisund';
$string['privacy:metadata:newsletter_bounces:timecreated'] = 'Zeitpunkt an dem der Eintrag erstellt wurde';
$string['privacy:metadata:newsletter_bounces:type'] = 'Bounce-Typ';
$string['privacy:metadata:newsletter_bounces:timereceived'] = 'Zeitpunkt an dem die Zurückweisung erhalten wurde';
$string['privacy:metadata:newsletter_deliveries'] = 'Zeige Newsletter, die dem/der Nutzer/in zugestellt wurden';
$string['privacy:metadata:newsletter_deliveries:userid'] = 'Nuetzer/in die/der den Newsletter erhalten hat';
$string['privacy:metadata:newsletter_deliveries:issueid'] = 'ID der zugesandten Newsletterausgabe';
$string['privacy:metadata:newsletter_deliveries:newsletterid'] = 'ID des Newsletters';
$string['privacy:metadata:newsletter_deliveries:delivered'] = 'Zeigt an, ob Newsletter zugestellt wurde';
