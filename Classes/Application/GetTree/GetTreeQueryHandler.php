<?php

/*
 * This script belongs to the package "Sitegeist.Archaeopteryx".
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Sitegeist\Archaeopteryx\Application\GetTree;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Sitegeist\Archaeopteryx\Application\Shared\TreeNode;
use Sitegeist\Archaeopteryx\Application\Shared\TreeNodeBuilder;
use Sitegeist\Archaeopteryx\Infrastructure\ContentRepository\NodeSearchService;
use Sitegeist\Archaeopteryx\Infrastructure\ContentRepository\NodeSearchSpecification;
use Sitegeist\Archaeopteryx\Infrastructure\ContentRepository\NodeTypeFilter;
use Sitegeist\Archaeopteryx\Infrastructure\ContentRepository\TreeBuilder;

/**
 * @internal
 */
#[Flow\Scope("singleton")]
final class GetTreeQueryHandler
{
    #[Flow\Inject]
    protected ContentContextFactory $contentContextFactory;

    #[Flow\Inject]
    protected NodeTypeManager $nodeTypeManager;

    #[Flow\Inject]
    protected NodeSearchService $nodeSearchService;

    #[Flow\Inject]
    protected NodeTypeConstraintFactory $nodeTypeConstraintFactory;

    public function handle(GetTreeQuery $query): GetTreeQueryResult
    {
        $contentContext = $this->contentContextFactory->create([
            'workspaceName' => $query->workspaceName,
            'dimensions' => $query->dimensionValues,
            'targetDimensions' => $query->getTargetDimensionValues(),
            'invisibleContentShown' => true,
            'removedContentShown' => false,
            'inaccessibleContentShown' => true
        ]);

        $rootNode = $contentContext->getNode((string) $query->startingPoint);
        if (!$rootNode instanceof Node) {
            throw new \Exception('Forget it!');
        }

        return new GetTreeQueryResult(
            root: $tree = empty($query->searchTerm) && empty($query->leafNodeTypeFilter)
                ? $this->loadTree($rootNode, $query, $query->loadingDepth)
                : $this->performSearch($rootNode, $query),
        );
    }

    private function performSearch(Node $rootNode, GetTreeQuery $query): TreeNode
    {
        $baseNodeTypeFilter = NodeTypeFilter::fromFilterString(
            $query->baseNodeTypeFilter,
            $this->nodeTypeConstraintFactory,
            $this->nodeTypeManager,
        );
        $leafNodeTypeFilter = NodeTypeFilter::fromFilterString(
            $query->leafNodeTypeFilter,
            $this->nodeTypeConstraintFactory,
            $this->nodeTypeManager,
        );

        $matchingNodes = $this->nodeSearchService->search(
            $query->searchTerm,
            $leafNodeTypeFilter,
            $rootNode
        );

        $treeBuilder = TreeBuilder::forRootNode(
            rootNode: $rootNode,
            nodeSearchSpecification: new NodeSearchSpecification(
                baseNodeTypeFilter: $baseNodeTypeFilter,
                leafNodeTypeFilter: $leafNodeTypeFilter,
                searchTerm: $query->searchTerm
            )
        );

        foreach ($matchingNodes as $matchingNode) {
            $treeBuilder->addNodeWithAncestors($matchingNode);
        }

        return $treeBuilder->build();
    }

    private function loadTree(Node $node, GetTreeQuery $query, int $remainingDepth): TreeNode
    {
        $baseNodeTypeFilter = NodeTypeFilter::fromFilterString(
            $query->baseNodeTypeFilter,
            $this->nodeTypeConstraintFactory,
            $this->nodeTypeManager,
        );

        $treeBuilder = TreeBuilder::forRootNode(
            rootNode: $node,
            nodeSearchSpecification: new NodeSearchSpecification(
                baseNodeTypeFilter: $baseNodeTypeFilter,
                leafNodeTypeFilter: null,
                searchTerm: null
            )
        );

        $treeBuilder->addNodeWithDescendants($node, $remainingDepth);

        if ($query->selectedNodeId) {
            $selectedNode = $node->getContext()->getNodeByIdentifier((string) $query->selectedNodeId);
            if ($selectedNode) {
                /** @var Node $selectedNode */
                $treeBuilder->addNodeWithSiblingsAndAncestors($selectedNode);
            }
        }

        return $treeBuilder->build();
    }
}
