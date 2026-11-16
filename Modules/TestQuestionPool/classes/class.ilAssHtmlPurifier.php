<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package     Modules/Test
 */
abstract class ilAssHtmlPurifier extends ilHtmlPurifierAbstractLibWrapper
{
    protected function getPurifierType(): string
    {
        return 'assessment';
    }

    /**
     * @return	HTMLPurifier_Config Instance of HTMLPurifier_Config
     */
    protected function getPurifierConfigInstance(): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.DefinitionID', $this->getPurifierType());
        // increased the number from 1 to 2 (to 3) in order to build a new version using my changes (ie new version number so htmlpurifier wont reuse an older cashed definition)
        $config->set('HTML.DefinitionRev', 3);
        $config->set('Cache.SerializerPath', ilHtmlPurifierAbstractLibWrapper::_getCacheDirectory());
        $config->set('HTML.Doctype', 'XHTML 1.0 Strict');
        $config->set('HTML.AllowedElements', $this->getAllowedElements());
        $config->set('HTML.ForbiddenAttributes', 'div@style');
        $config->autoFinalize = false;
        $config->set(
            'URI.AllowedSchemes',
            array_merge(
                $config->get('URI.AllowedSchemes'),
                ['data' => true]
            )
        );
        $config->autoFinalize = true;
        if ($def = $config->maybeGetRawHTMLDefinition()) {

            // adding in experimental support for a simple MathML fraction.
            $def->addElement('math', 'Inline', 'Optional: mfrac', []);
            $def->addElement('mfrac', false, 'Optional: mi | mn', []);
            $def->addElement('mi', false, 'Optional: #PCDATA', []);
            $def->addElement('mn', false, 'Optional: #PCDATA', []);

            // adding in experimental support for FTML attributes
            // Experimental preservation of FTML attributes on span elements.
            $def->addAttribute('span', 'data-ftml-term', 'Text');
            $def->addAttribute('span', 'data-ftml-head', 'Text');
            $def->addAttribute('span', 'data-ftml-comp', 'Text');

            $def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');

            // fau: nobrElement - add nobr to the known elements of html purifier
            $def->addElement(
                'nobr',   // name
                'Flow',  // content set
                'Flow', // allowed children
                '', // attribute collection
                array(
                )
            );
            // fau.
        }

        return $config;
    }

    private function getAllowedElements(): array
    {
        $allowedElements = $this->getElementsUsedForAdvancedEditing();

        $allowedElements = $this->makeElementListTinyMceCompliant($allowedElements);
        $allowedElements = $this->removeUnsupportedElements($allowedElements);

        // add mathml elements to the allowed elements list so mathml can be used in questions and answers
        $allowedElements = array_merge(
        $allowedElements,
        ['math', 'mfrac', 'mi', 'mn']
    );

        return $allowedElements;
    }

    private function getElementsUsedForAdvancedEditing(): array
    {
        return ilObjAdvancedEditing::_getUsedHTMLTags($this->getPurifierType());
    }
}
