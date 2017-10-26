<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Player;
use AppBundle\Form\PlayerType;
use AppBundle\Form\TeamType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // lista drużyn
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Team');
        $teams = $repo->findAll();
        return $this->render('AppBundle:lists:teams.html.twig', array(
            'teams' => $teams,
        ));

    }

    /**
     * @Route("/addteam", name="addNewTeam")
     */
    public function addTeamAction(Request $request) {
        $form = $this->createForm(TeamType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $dane = $form->getData();
            $em->persist($dane);
            $em->flush();
            return $this->redirectToRoute("homepage");
        }

        return $this->render('AppBundle:forms:addTeam.html.twig', array(
            'form' => $form->createView(),
            'action' => $this->generateUrl('addNewTeam'),
        ));
    }
    /**
     * @Route("/addplayer", name="addNewPlayer")
     */
    public function addPlayer(Request $request) {
        $maxPlayersForTeam =3;
        $form = $this->createForm(PlayerType::class);
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        if($session->has('formData')) {

            $data = $session->get('formData');
            $data->setTeam(null);
            $form = $this->createForm(PlayerType::class, $data);
            $session->remove('formData');
        }
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $dane = $form->getData();
            $teamToCheck = $dane->getTeam();
            $playersList = $em->getRepository('AppBundle:Player')
                ->findBy(array('team' =>$teamToCheck));
            if(count($playersList)==$maxPlayersForTeam) {
                $session->set('formData', $dane);
                return $this->render('AppBundle:forms/ErrorMessages:tooManyPlayers.html.twig', array(
                    'team' => $dane->getTeam(),
                ));
            }
            $em->persist($dane);
            $em->flush();
            return $this->redirectToRoute("homepage");
        }

        return $this->render('AppBundle:forms:addPlayer.html.twig', array(
            'form' => $form->createView(),
            'action' => $this->generateUrl('addNewPlayer'),
        ));
    }
    /**
     * @Route("/players/{teamId}", name="playersTeam")
     */
    public function playersTeamListAction(Request $request, $teamId) {
        $em = $this->getDoctrine()->getManager();
        $team = $em->getRepository('AppBundle:Team')->find($teamId);
        $players = $em->getRepository('AppBundle:Player')->findBy(array(
           'team' => $team
        ));
        return $this->render('AppBundle:lists:players.html.twig', array(
            'team' => $team,
            'players' => $players
        ));
    }
    /**
     * @Route("/deleteTeam/{id}", name="deleteTam")
     */
    public function deleteTeamAction( Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $team = $em->getRepository('AppBundle:Team')->find($id);
        $em->remove($team);
        $em->flush();
        return ($this->redirectToRoute('homepage'));
    }
    /**
     * @Route("/deletePlayer/{teamId}/{playerId}", name="deletePlayer")
     */
    public function deletePlayerAction(Request $request, $teamId, $playerId) {
        $em = $this->getDoctrine()->getManager();
        $player = $em->getRepository('AppBundle:Player')->find($playerId);
        $em->remove($player);
        $em->flush();
        return $this->redirectToRoute('playersTeam', array('teamId' => $teamId));
    }
}
