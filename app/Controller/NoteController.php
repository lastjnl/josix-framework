<?php

declare(strict_types=1);

namespace Josix\Controller;

use Josix\Core\Http\Response;
use Josix\Core\Routing\Route;
use Josix\Model\Note;
use Josix\Repository\NoteRepository;

class NoteController extends Controller
{
    public function __construct(
        private readonly NoteRepository $notes,
    ) {
    }

    #[Route(path: '/notes', method: 'GET', name: 'notes.index')]
    public function index(): Response
    {
        $notes = $this->notes->findAllOrderedByNewest();

        return new Response($this->render('notes.html.twig', [
            'title' => 'Notes — Josix SQLite Demo',
            'notes' => $notes,
        ]));
    }

    #[Route(path: '/notes', method: 'POST', name: 'notes.store')]
    public function store(): void
    {
        $title = trim($_POST['title'] ?? '');
        $body  = trim($_POST['body'] ?? '');

        if ($title !== '') {
            $note = new Note();
            $note->set('title', $title);
            $note->set('body', $body);
            $note->set('created_at', date('Y-m-d H:i:s'));

            $this->notes->save($note);
        }

        header('Location: /notes');
        exit;
    }

    #[Route(path: '/notes/{id}/delete', method: 'POST', name: 'notes.delete')]
    public function delete(int $id): void
    {
        $this->notes->delete($id);

        header('Location: /notes');
        exit;
    }
}
