<?php

namespace CodeProject\Services;

use CodeProject\Repositories\ProjectRepository;
use CodeProject\Validators\ProjectValidator;
use CodeProject\Validators\ProjectFileValidator;
use Prettus\Validator\Exceptions\ValidatorException;


use Illuminate\Contracts\Filesystem\Factory as Storage;
use Illuminate\Filesystem\Filesystem;

class ProjectService
{
    protected $repository;
    protected $validator;
    protected $fileValidator;
    protected $storage;
    protected $fileSystem;

    public function __construct(ProjectRepository $repository, ProjectValidator $validator, ProjectFileValidator $fileValidator,  Storage $storage, Filesystem $filesystem)
    {
        $this->repository = $repository;
        $this->validator = $validator;
        $this->fileValidator = $fileValidator;
        $this->storage = $storage;
        $this->fileSystem = $filesystem;
    }

    public function create(array $data)
    {
        try{
            $this->validator->with($data)->passesOrFail();
            return $this->repository->create($data);
        }
        catch(ValidatorException $e){
            $error = $e->getMessageBag();
            return [
                'error' => true,
                'message' => "Erro ao cadastrar o projeto, alguns campos são obrigatórios!",
                'messages' => $error->getMessages(),
            ];
        }
    }

    public function createFile(array $data)
    {
        try{
            $this->fileValidator->with($data)->passesOrFail();
            $project = $this->repository->skipPresenter()->find($data['project_id']);
            $projectFile = $project->files()->create($data);

            $this->storage->put($projectFile->id.".".$data['extension'], $this->fileSystem->get($data['file']));
        }
        catch(ValidatorException $e){
            $error = $e->getMessageBag();
            return [
                'error' => true,
                'message' => "Erro ao enviar o arquivo, alguns campos são obrigatórios!",
                'messages' => $error->getMessages(),
            ];
        }
    }

    public function update(array $data, $id)
    {
        try{
            $this->validator->with($data)->passesOrFail();
            return $this->repository->update($data, $id);
        }
        catch(ValidatorException $e){
            $error = $e->getMessageBag();
            return [
                'error' => true,
                'message' => "Erro ao atualizar o projeto, alguns campos são obrigatórios!",
                'messages' => $error->getMessages(),
            ];
        }
    }

    public function addMember($project_id, $member_id)
    {
        $project = $this->repository->find($project_id);

        if(!$this->isMember($project_id, $member_id)){
            $project->members()->attach($member_id);
        }

        return $project->members()->get();
    }

    public function removeMember($project_id, $member_id)
    {
        $project = $this->repository->find($project_id);
        $project->members()->detach($member_id);
        return $project->members()->get();
    }

    public function isMember($project_id, $member_id)
    {
        $project = $this->repository->find($project_id)->members()->find(['member_id' => $member_id]);

        if(count($project)){
            return true;
        }

        return false;
    }
}