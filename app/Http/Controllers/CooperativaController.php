<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Mail\PedidoEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

session_start();

class CooperativaController extends Controller
{
    public function viewCadastroUsuario(){
        if(isset($_COOKIE['usuario']) || isset($_COOKIE['cooperativa'])){
            AlertController::alert("Você precisa sair da sua conta antes de cadastrar outra.", "danger");
            return redirect("/");
        }else{
            return view('cooperativa.cadastro');
        }
    }

    public function viewCooperativa(){
        $nome_cooperativa = request("cooperativa");
        $cooperativa = DB::select("SELECT * FROM tb_cooperativas WHERE nome = ?", [$nome_cooperativa]);
        $produtos = DB::select("SELECT * FROM tb_produtos WHERE id_cooperativa = ? ORDER BY id DESC;", [$cooperativa[0]->id]);
        $coop_info = array(
            $cooperativa[0]->historico,
            $cooperativa[0]->missao,
            $cooperativa[0]->visao,
            $cooperativa[0]->valores,
            $cooperativa[0]->endereco,
            $cooperativa[0]->tel1
        );
        $count = 0;
        foreach ($coop_info as $info) {
            if($info){
                $count++;
            }
        }
        $impar = false;
        if($count % 2 == 0){
            $impar = false;
        }else{
            $impar = true;
        }
        return view('cooperativa.view.cooperativa', compact('cooperativa', 'produtos', 'impar'));
    }

    public function viewProdutos(){
        $produtos = DB::select("SELECT * FROM tb_produtos WHERE id_cooperativa = ? ORDER BY id DESC;", [$_COOKIE['cooperativa']]);
        return view('cooperativa.produtos.meusprodutos', compact('produtos'));
    }

    public function addProduto(Request $request): string{
        $id_cooperativa = $_COOKIE["cooperativa"];
        $nome = request("nome");
        $descricao = request("descricao");
        $preco = request("preco");
        $quantidade = request("quantidade");
        $entrega = request("entrega");

        if($request->file('imagem')){
            $path = $request->file('imagem')->storeAs('images/produtos', "pimg".$nome, 'public');
        }else{
            $path = "images/produtos/default_template.jpg";
        }

        if($quantidade == 0){
            $status = 0;
        }else{
            $status = 1;
        }
        
        DB::insert("INSERT INTO 
                    tb_produtos (id_cooperativa, nome, descricao, preco, quantidade, imagem, status, entrega) 
                    VALUES (?, ?, ? ,?, ?, ?, ?, ?)", 
                    [$id_cooperativa, $nome, $descricao, $preco, $quantidade, $path, $status, $entrega]);

        AlertController::alert("Produto cadastrado com sucesso.", "success");
        return redirect("/meusprodutos");
    }

    public function updateProduto(Request $request): string{
        $id = request("id");
        $id_cooperativa = $_COOKIE["cooperativa"];
        $nome = request("nome");
        $descricao = request("descricao");
        $preco = request("preco");
        $quantidade = request("quantidade");
        $acao = request("acao");
        $entrega = request("entrega");

        if($quantidade == 0){
            $status = 0;
        }else{
            $status = 1;
        }

        if($acao == "deletar"){
            $img = DB::select("SELECT imagem FROM tb_produtos WHERE id_cooperativa = ? AND id = ?;", [$id_cooperativa, $id])[0]->imagem;
            @unlink("storage/".$img);
            DB::delete("DELETE FROM tb_produtos WHERE id = ? AND id_cooperativa = ?", [$id, $id_cooperativa]);
            AlertController::alert("Produto deletado com sucesso.", "warning");
        }else if($acao == "atualizar"){
            if($request->file('imagem')){
                $img = DB::select("SELECT imagem FROM tb_produtos WHERE id_cooperativa = ? AND id = ?;", [$id_cooperativa, $id])[0]->imagem;
                @unlink("storage/".$img);
                $path = $request->file('imagem')->storeAs('images/produtos', "pimg".$nome, 'public');
                DB::update("UPDATE tb_produtos SET nome = ?, descricao = ?, preco = ?, quantidade = ?, imagem = ?, status = ?, entrega = ? WHERE id = ? AND id_cooperativa = ?;", 
                [$nome, $descricao, $preco, $quantidade, $path, $status, $entrega, $id, $id_cooperativa]);
            }else{
                DB::update("UPDATE tb_produtos SET nome = ?, descricao = ?, preco = ?, quantidade = ?, status = ?, entrega = ? WHERE id = ? AND id_cooperativa = ?;", 
                [$nome, $descricao, $preco, $quantidade, $status, $entrega, $id, $id_cooperativa]);
            }
            AlertController::alert("Produto atualizado com sucesso.", "success");
        }
        return redirect("/meusprodutos");
    }

    public function updateCooperativa(Request $request): string{
        $id = $_COOKIE["cooperativa"];
        $nome = request("nome");
        setcookie("nome_cooperativa", $nome, time() + (86400 * 30), "/");
        $descricao = request("descricao");
        $historico = request("historico");
        $missao = request("missao");
        $visao = request("visao");
        $valores = request("valores");
        $endereco = request("endereco");
        $cep = request("cep");
        $tel1 = request("tel1");
        $tel2 = request("tel2");
        $whatsapp = request("whatsapp");
        $instagram = request("instagram");
        $facebook = request("facebook");
        $cooperativa = DB::select("SELECT * FROM tb_cooperativas WHERE id = ?", [$id]);
        $outdoor = $cooperativa[0]->outdoor;
        $perfil = $cooperativa[0]->perfil;

        if($request->file('outdoor')){
            $img = DB::select("SELECT outdoor FROM tb_cooperativas WHERE id = ?;", [$id])[0]->outdoor;
            @unlink("storage/".$img);
            $outdoor = $request->file('outdoor')->storeAs('images/coopertivas', "outdoor".$nome, 'public');
        }
        if($request->file('perfil')){
            $img = DB::select("SELECT perfil FROM tb_cooperativas WHERE id = ?;", [$id])[0]->perfil;
            @unlink("storage/".$img);
            $perfil = $request->file('perfil')->storeAs('images/coopertivas', "perfil".$nome, 'public');
            setcookie("perfil_img", $perfil, time() + (86400 * 30), "/");
        }

        DB::update("UPDATE tb_cooperativas SET nome = ?, descricao = ?, historico = ?, missao = ?, visao = ?, valores = ?, endereco = ?, cep = ?, tel1 = ?, tel2 = ?, whatsapp = ?, instagram = ?, facebook = ?, outdoor = ?, perfil = ? WHERE id = ?;", 
        [$nome, $descricao, $historico, $missao, $visao, $valores, $endereco, $cep, $tel1, $tel2, $whatsapp, $instagram, $facebook, $outdoor, $perfil, $id]);

        AlertController::alert("Cooperativa atualizada com sucesso.", "success");
        return redirect("/cooperativa/".$nome);
    }


    public function addCooperativa(Request $request): string{
        $nome = request("nome");
        $email = request("email");
        $cep = request("cep");
        $endereco = request("endereco");
        $tipo = request("tipo");
        $cnpj = request("cnpj");
        if(!$this->validarCNPJ($cnpj)){
            AlertController::alert("CNPJ Inválido", "danger");
            return redirect("/cadastrar/cooperativa");
        }
        $senha = Hash::make(request("senha"));
        $tel1 = request("tel1");
        $tel2 = request("tel2");
        $whatsapp = request("whatsapp");
        $instagram = request("instagram");
        $facebook = request("facebook");
        $descricao = request("descricao");
        $token = Str::random(60);

        if($request->file('outdoor') || $request->file('perfil')){
            $outdoor = $request->file('imagem')->storeAs('images/coopertivas', "outdoor".$nome, 'public');
            $perfil = $request->file('perfil')->storeAs('images/coopertivas', "perfil".$nome, 'public');
        }else{
            $outdoor = NULL;
            $perfil = "images/produtos/default_template.jpg";
        }

        $cooperativas = DB::select("SELECT email, cnpj FROM tb_cooperativas WHERE email = ? OR cnpj = ?;", [$email, $cnpj]);

        if(count($cooperativas) > 0){
            AlertController::alert("E-mail ou CNPJ já cadastrado, tente novamente", "warning");
            return redirect("/cadastrar/cooperativa");
        }else{
            DB::insert('INSERT INTO tb_cooperativas 
            (nome, email, cep, endereco, tipo, cnpj, senha, tel1, tel2, whatsapp, instagram, facebook, descricao, perfil, outdoor, token, ativa)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0);', 
            [$nome, $email, $cep, $endereco, $tipo, $cnpj, $senha, $tel1, $tel2, $whatsapp, $instagram, $facebook, $descricao , $perfil, $outdoor, $token]);

            $this->enviarEmail($email, $token);
            AlertController::alert("Confirme o endereço de email para ultilizar a conta", "warning");
            return redirect("/entrar");
        }

    }

    public function enviarEmail($email, $token){
        $dados = [
        'link' => 'https://cooperativasunidas.online/validar/cooperativa/'.$token
        ];
        
        Mail::to($email)->send(new PedidoEmail($dados, "confirmarEmail"));
    }


    function validarCNPJ($cnpj) {
    if ($cnpj == '00000000000000' || 
        $cnpj == '11111111111111' || 
        $cnpj == '22222222222222' || 
        $cnpj == '33333333333333' || 
        $cnpj == '44444444444444' || 
        $cnpj == '55555555555555' || 
        $cnpj == '66666666666666' || 
        $cnpj == '77777777777777' || 
        $cnpj == '88888888888888' || 
        $cnpj == '99999999999999') {
        return false;

        } else {   
         
            $j = 5;
            $k = 6;
            $soma1 = 0;
            $soma2 = 0;
    
            for ($i = 0; $i < 13; $i++) {
    
                $j = $j == 1 ? 9 : $j;
                $k = $k == 1 ? 9 : $k;
    
                $soma2 += ($cnpj[$i] * $k);
    
                if ($i < 12) {
                    $soma1 += ($cnpj[$i] * $j);
                }
                $k--;
                $j--;
            }
    
            $digito1 = $soma1 % 11 < 2 ? 0 : 11 - $soma1 % 11;
            $digito2 = $soma2 % 11 < 2 ? 0 : 11 - $soma2 % 11;
    
            return (($cnpj[12] == $digito1) and ($cnpj[13] == $digito2));
         
        }
    }
}
