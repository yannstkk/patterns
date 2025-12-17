package src.exo2;

public class AppEnt {

    public static void main(String[] args) {
        
        Commande cmd1 = CommandeFactory.creerCommande("STANDARD");

        Cadeau cadeau = new Cadeau(cmd1);

        cadeau.steps();



    }


    
    
}
