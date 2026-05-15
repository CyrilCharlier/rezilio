<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Enum\MeasureReviewStatus;
use App\Form\Campaign\CampaignContextType;
use App\Form\Campaign\CampaignScheduleType;
use App\Form\Model\CampaignCreationModel;
use App\Repository\CampaignRepository;
use App\Repository\MeasureNodeRepository;
use App\Repository\MeasureReviewRepository;
use App\Repository\ReferentialRepository;
use App\Repository\SocietyRepository;
use App\Service\Campaign\CampaignCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/campaign')]
final class CampaignController extends AbstractController
{
    private const SESSION_KEY = 'campaign_wizard';

    #[Route('', name: 'app_campaign_index', methods: ['GET'])]
    public function index(
        Request $request,
        CampaignRepository $campaignRepository,
        SocietyRepository $societyRepository
    ): Response {
        $search = trim((string) $request->query->get('search', ''));
        $societyId = $request->query->get('society');

        $societies = $societyRepository->findBy([], ['name' => 'ASC']);

        $campaigns = $campaignRepository->findByFilters(
            $search ?: null,
            $societyId ? (int) $societyId : null
        );

        return $this->render('campaign/index.html.twig', [
            'campaigns' => $campaigns,
            'societies' => $societies,
            'filters' => [
                'search' => $search,
                'society' => $societyId,
            ],
        ]);
    }

    #[Route('/new', name: 'app_campaign_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        SessionInterface $session,
        CampaignCreator $campaignCreator,
        MeasureNodeRepository $measureNodeRepository,
        SocietyRepository $societyRepository,
        ReferentialRepository $referentialRepository,
    ): Response {
        $steps = [
            'context' => 'Contexte',
            'schedule' => 'Période',
            'confirm' => 'Validation',
        ];

        $step = $request->query->get('step', 'context');

        if (!array_key_exists($step, $steps)) {
            $step = 'context';
        }

        $payload = $session->get(self::SESSION_KEY, []);

        $data = new CampaignCreationModel();
        $data->name = $payload['name'] ?? null;
        $data->society = !empty($payload['society_id'])
            ? $societyRepository->find((int) $payload['society_id'])
            : null;
        $data->referential = !empty($payload['referential_id'])
            ? $referentialRepository->find((int) $payload['referential_id'])
            : null;
        $data->startDate = !empty($payload['start_date'])
            ? new \DateTimeImmutable($payload['start_date'])
            : null;
        $data->endDate = !empty($payload['end_date'])
            ? new \DateTimeImmutable($payload['end_date'])
            : null;

        $form = $this->createStepForm($step, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {

            } else {
                $session->set(self::SESSION_KEY, [
                    'name' => $data->name,
                    'society_id' => $data->society?->getId(),
                    'referential_id' => $data->referential?->getId(),
                    'start_date' => $data->startDate?->format('Y-m-d'),
                    'end_date' => $data->endDate?->format('Y-m-d'),
                ]);

                if ('context' === $step) {
                    return $this->redirectToRoute('app_campaign_new', ['step' => 'schedule']);
                }

                if ('schedule' === $step) {
                    return $this->redirectToRoute('app_campaign_new', ['step' => 'confirm']);
                }

                if ('confirm' === $step) {
                    $campaign = $campaignCreator->createFromModel($data);

                    $session->remove(self::SESSION_KEY);

                    $this->addFlash('success', sprintf(
                        'La campagne "%s" a été créée avec succès.',
                        $campaign->getName()
                    ));

                    return $this->redirectToRoute('app_campaign_index');
                }
            }
        }

        $measureCount = null;
        if (null !== $data->referential) {
            $measureCount = $measureNodeRepository->countLeafNodesByReferential($data->referential);
        }

        $stepIndex = array_search($step, array_keys($steps), true);
        if (false === $stepIndex) {
            $stepIndex = 0;
        }

        return $this->render('campaign/new.html.twig', [
            'form' => $form->createView(),
            'step' => $step,
            'steps' => $steps,
            'stepIndex' => $stepIndex,
            'wizard' => $data,
            'measureCount' => $measureCount,
        ]);
    }

    private function createStepForm(string $step, CampaignCreationModel $data)
    {
        return match ($step) {
            'context' => $this->createForm(CampaignContextType::class, $data),
            'schedule' => $this->createForm(CampaignScheduleType::class, $data),
            'confirm' => $this->createForm(FormType::class, $data),
            default => $this->createForm(CampaignContextType::class, $data),
        };
    }

    #[Route('/{id}', name: 'app_campaign_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        Campaign $campaign,
        Request $request,
        MeasureReviewRepository $measureReviewRepository
    ): Response {
        $search = trim((string) $request->query->get('search', ''));
        $categoryId = $request->query->get('category');
        $status = trim((string) $request->query->get('status', ''));
        $applicability = trim((string) $request->query->get('applicability', ''));

        $reviews = $measureReviewRepository->findKanbanCardsByCampaign(
            $campaign,
            [
                'search' => $search ?: null,
                'category' => $categoryId ? (int) $categoryId : null,
                'status' => $status ?: null,
                'applicability' => $applicability ?: null,
            ]
        );

        $columns = [
            'todo' => [
                'label' => 'À qualifier',
                'value' => MeasureReviewStatus::NON_COMPLIANT->value,
                'items' => [],
            ],
            'in_progress' => [
                'label' => 'En cours',
                'value' => MeasureReviewStatus::IN_PROGRESS->value,
                'items' => [],
            ],
            'blocked' => [
                'label' => 'Bloquée',
                'value' => MeasureReviewStatus::BLOCKED->value,
                'items' => [],
            ],
            'done' => [
                'label' => 'Conforme / validée',
                'value' => MeasureReviewStatus::COMPLIANT->value,
                'items' => [],
            ],
        ];

        foreach ($reviews as $review) {
            $reviewStatus = match ($review->getStatus()) {
                MeasureReviewStatus::NON_COMPLIANT => 'todo',
                MeasureReviewStatus::IN_PROGRESS => 'in_progress',
                MeasureReviewStatus::BLOCKED => 'blocked',
                MeasureReviewStatus::COMPLIANT => 'done',
            };

            $columns[$reviewStatus]['items'][] = $review;
        }

        return $this->render('campaign/show.html.twig', [
            'campaign' => $campaign,
            'columns' => $columns,
            'filters' => [
                'search' => $search,
                'category' => $categoryId,
                'status' => $status,
                'applicability' => $applicability,
            ],
            'categories' => $measureReviewRepository->findCategoriesByCampaign($campaign),
            'stats' => [
                'total' => count($reviews),
            ],
        ]);
    }

}
