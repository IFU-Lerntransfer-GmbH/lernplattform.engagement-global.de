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
 * @package		Modules/Test
 */
abstract class ilTestRandomQuestionSetBuilder implements ilTestRandomSourcePoolDefinitionQuestionCollectionProvider
{
    /**
     * @var ilDBInterface
     */
    protected $db = null;

    /**
     * @var ilObjTest
     */
    protected $testOBJ = null;

    /**
     * @var ilTestRandomQuestionSetConfig
     */
    protected $questionSetConfig = null;

    /**
     * @var ilTestRandomQuestionSetSourcePoolDefinitionList
     */
    protected $sourcePoolDefinitionList = null;

    /**
     * @var ilTestRandomQuestionSetStagingPoolQuestionList
     */
    protected $stagingPoolQuestionList = null;

    //fau: fixRandomTestBuildable - variable for messages
    protected $checkMessages = array();
    // fau.

    /**
     * @param ilDBInterface $db
     * @param ilObjTest $testOBJ
     * @param ilTestRandomQuestionSetConfig $questionSetConfig
     * @param ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList
     * @param ilTestRandomQuestionSetStagingPoolQuestionList $stagingPoolQuestionList
     */
    protected function __construct(
        ilDBInterface $db,
        ilObjTest $testOBJ,
        ilTestRandomQuestionSetConfig $questionSetConfig,
        ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList,
        ilTestRandomQuestionSetStagingPoolQuestionList $stagingPoolQuestionList
    ) {
        $this->db = $db;
        $this->testOBJ = $testOBJ;
        $this->questionSetConfig = $questionSetConfig;
        $this->sourcePoolDefinitionList = $sourcePoolDefinitionList;
        $this->stagingPoolQuestionList = $stagingPoolQuestionList;
        $this->stagingPoolQuestionList->setTestObjId($this->testOBJ->getId());
        $this->stagingPoolQuestionList->setTestId($this->testOBJ->getTestId());
    }

    abstract public function checkBuildable();

    abstract public function performBuild(ilTestSession $testSession);

    public function getSrcPoolDefListRelatedQuestCombinationCollection(ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList): ilTestRandomQuestionSetQuestionCollection
    {
        $questionStage = new ilTestRandomQuestionSetQuestionCollection();

        foreach ($sourcePoolDefinitionList as $definition) {
            $questions = $this->getSrcPoolDefRelatedQuestCollection($definition);
            $questionStage->mergeQuestionCollection($questions);
        }

        return $questionStage;
    }

    /**
     * @param ilTestRandomQuestionSetSourcePoolDefinition $definition
     * @return ilTestRandomQuestionSetQuestionCollection
     */
    public function getSrcPoolDefRelatedQuestCollection(ilTestRandomQuestionSetSourcePoolDefinition $definition): ilTestRandomQuestionSetQuestionCollection
    {
        $questionIds = $this->getQuestionIdsForSourcePoolDefinitionIds($definition);
        $questionStage = $this->buildSetQuestionCollection($definition, $questionIds);

        return $questionStage;
    }

    // hey: fixRandomTestBuildable - rename/public-access to be aware for building interface
    /**
     * @param ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList
     * @return ilTestRandomQuestionSetQuestionCollection
     */
    public function getSrcPoolDefListRelatedQuestUniqueCollection(ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList): ilTestRandomQuestionSetQuestionCollection
    {
        $combinationCollection = $this->getSrcPoolDefListRelatedQuestCombinationCollection($sourcePoolDefinitionList);
        return $combinationCollection->getUniqueQuestionCollection();
    }
    // hey.

    private function getQuestionIdsForSourcePoolDefinitionIds(ilTestRandomQuestionSetSourcePoolDefinition $definition): array
    {
        $this->stagingPoolQuestionList->resetQuestionList();

        $this->stagingPoolQuestionList->setPoolId($definition->getPoolId());

        if ($this->hasTaxonomyFilter($definition)) {
            foreach ($definition->getMappedTaxonomyFilter() as $taxId => $nodeIds) {
                $this->stagingPoolQuestionList->addTaxonomyFilter($taxId, $nodeIds);
            }
        }

        if (count($definition->getLifecycleFilter())) {
            $this->stagingPoolQuestionList->setLifecycleFilter($definition->getLifecycleFilter());
        }

        // fau: taxFilter/typeFilter - use type filter
        if ($this->hasTypeFilter($definition)) {
            $this->stagingPoolQuestionList->setTypeFilter($definition->getTypeFilter());
        }
        // fau.

        $this->stagingPoolQuestionList->loadQuestions();

        return $this->stagingPoolQuestionList->getQuestions();
    }

    private function buildSetQuestionCollection(ilTestRandomQuestionSetSourcePoolDefinition $definition, $questionIds): ilTestRandomQuestionSetQuestionCollection
    {
        $setQuestionCollection = new ilTestRandomQuestionSetQuestionCollection();

        foreach ($questionIds as $questionId) {
            $setQuestion = new ilTestRandomQuestionSetQuestion();

            $setQuestion->setQuestionId($questionId);
            $setQuestion->setSourcePoolDefinitionId($definition->getId());

            $setQuestionCollection->addQuestion($setQuestion);
        }

        return $setQuestionCollection;
    }

    private function hasTaxonomyFilter(ilTestRandomQuestionSetSourcePoolDefinition $definition): bool
    {
        if (!count($definition->getMappedTaxonomyFilter())) {
            return false;
        }
        return true;
    }

    //	fau: typeFilter - check for existing type filter
    private function hasTypeFilter(ilTestRandomQuestionSetSourcePoolDefinition $definition): bool
    {
        if (count($definition->getTypeFilter())) {
            return true;
        }

        return false;
    }
    //	fau.

    protected function storeQuestionSet(ilTestSession $testSession, $questionSet)
    {
        $position = 0;

        foreach ($questionSet->getQuestions() as $setQuestion) {
            /* @var ilTestRandomQuestionSetQuestion $setQuestion */

            $setQuestion->setSequencePosition($position++);

            $this->storeQuestion($testSession, $setQuestion);
        }
    }

    private function storeQuestion(ilTestSession $testSession, ilTestRandomQuestionSetQuestion $setQuestion)
    {
        $nextId = $this->db->nextId('tst_test_rnd_qst');

        $this->db->insert('tst_test_rnd_qst', array(
            'test_random_question_id' => array('integer', $nextId),
            'active_fi' => array('integer', $testSession->getActiveId()),
            'question_fi' => array('integer', $setQuestion->getQuestionId()),
            'sequence' => array('integer', $setQuestion->getSequencePosition()),
            'pass' => array('integer', $testSession->getPass()),
            'tstamp' => array('integer', time()),
            'src_pool_def_fi' => array('integer', $setQuestion->getSourcePoolDefinitionId())
        ));
    }

    protected function fetchQuestionsFromStageRandomly(ilTestRandomQuestionSetQuestionCollection $questionStage, $requiredQuestionAmount): ilTestRandomQuestionSetQuestionCollection
    {
        $questionSet = $questionStage->getRandomQuestionCollection($requiredQuestionAmount);

        return $questionSet;
    }

    protected function handleQuestionOrdering(ilTestRandomQuestionSetQuestionCollection $questionSet)
    {
        if ($this->testOBJ->getShuffleQuestions()) {
            $questionSet->shuffleQuestions();
        }
    }

    // =================================================================================================================

    final public static function getInstance(
        ilDBInterface $db,
        ilObjTest $testOBJ,
        ilTestRandomQuestionSetConfig $questionSetConfig,
        ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList,
        ilTestRandomQuestionSetStagingPoolQuestionList $stagingPoolQuestionList
    ) {
        if ($questionSetConfig->isQuestionAmountConfigurationModePerPool()) {
            require_once 'Modules/Test/classes/class.ilTestRandomQuestionSetBuilderWithAmountPerPool.php';

            return new ilTestRandomQuestionSetBuilderWithAmountPerPool(
                $db,
                $testOBJ,
                $questionSetConfig,
                $sourcePoolDefinitionList,
                $stagingPoolQuestionList
            );
        }

        require_once 'Modules/Test/classes/class.ilTestRandomQuestionSetBuilderWithAmountPerTest.php';

        return new ilTestRandomQuestionSetBuilderWithAmountPerTest(
            $db,
            $testOBJ,
            $questionSetConfig,
            $sourcePoolDefinitionList,
            $stagingPoolQuestionList
        );
    }

    //fau: fixRandomTestBuildable - function to get messages
    /**
     * @return array
     */
    public function getCheckMessages(): array
    {
        return $this->checkMessages;
    }
    // fau.
}
