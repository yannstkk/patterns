package src.exo2;

public class Cadeau extends Decorator {


     public Cadeau(Commande commande) {
        super(commande);
    }

    @Override
    public int calculatePrice() {
        return super.calculatePrice() + 2;
    }

    @Override
    void pay() {
            System.out.println("le client a payee : "+calculatePrice());
    }
    
}
